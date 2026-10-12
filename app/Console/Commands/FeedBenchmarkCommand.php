<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\Rsvp;
use App\Models\User;
use App\Services\FeedService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MatanYadaev\EloquentSpatial\Objects\Point;

/**
 * Spec 001 (timeline performance) archaeology tool.
 *
 * Seeds a scratch dataset of a given size around a fixed centre point,
 * measures the nearby-feed query count, timing, and N+1 cost, then rolls
 * the dataset back. Nothing it seeds is kept.
 */
class FeedBenchmarkCommand extends Command
{
    protected $signature = 'feed:benchmark {--scales=100,1000,10000} {--iterations=20}';

    protected $description = 'Spec 001 archaeology: measure nearby-feed query counts and timings at scale (rolls back all seeded data)';

    private const CENTER_LAT = 33.9507556;

    private const CENTER_LNG = -83.9875616;

    /**
     * Other metro areas, far enough from the viewer that nothing placed near
     * them can ever fall inside the viewer's radius. Without this, a
     * single-city fixture makes almost every row "nearby", which hides the
     * real selectivity benefit (or cost) of a spatial index.
     *
     * @var list<array{0: float, 1: float}>
     */
    private const OTHER_METROS = [
        [40.7128, -74.0060],   // New York
        [34.0522, -118.2437],  // Los Angeles
        [41.8781, -87.6298],   // Chicago
        [47.6062, -122.3321],  // Seattle
    ];

    public function handle(FeedService $feedService): int
    {
        $scales = array_map('intval', explode(',', (string) $this->option('scales')));
        $iterations = (int) $this->option('iterations');

        $results = [];

        foreach ($scales as $scale) {
            $this->info("== scale: {$scale} posts ==");
            $results[$scale] = DB::transaction(function () use ($scale, $iterations, $feedService) {
                $result = $this->runScale($scale, $iterations, $feedService);
                DB::rollBack();

                return $result;
            }, 1);
        }

        $this->printSummary($results);

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function runScale(int $scale, int $iterations, FeedService $feedService): array
    {
        $viewer = User::factory()->create([
            'location_coordinates' => new Point(self::CENTER_LAT, self::CENTER_LNG, 4326),
        ]);

        $authors = User::factory()->count(20)->create([
            'location_coordinates' => new Point(self::CENTER_LAT, self::CENTER_LNG, 4326),
        ]);

        $this->output->write('  seeding posts... ');
        $postIds = $this->seedPosts($scale, $authors);
        $this->output->writeln('done');

        $eventScale = max(1, (int) round($scale / 10));
        $this->output->write('  seeding events... ');
        $eventIds = $this->seedEvents($eventScale, $authors);
        $this->output->writeln('done');

        $this->output->write('  seeding reactions/rsvps... ');
        $this->seedReactions($postIds, $authors);
        $this->seedRsvps($eventIds, $authors);
        $this->output->writeln('done');

        // --- C-01 / C-02: timing + query count for the first feed page ---
        // Items are plain FeedItem arrays (Ticket 1), not Eloquent models, so
        // there is no per-card N+1 or Livewire-rehydration query to simulate
        // here any more — that invariant is covered directly by
        // test_nearby_feed_query_count_is_constant and
        // test_reacting_to_post_runs_bounded_queries (Ticket 2).
        $timingsMs = [];
        $queryCount = null;
        $itemsReturned = 0;
        for ($i = 0; $i < $iterations; $i++) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $start = hrtime(true);
            $result = $feedService->getNearbyFeed(user: $viewer, eventRadiusKm: 50, perPage: 30);
            $timingsMs[] = (hrtime(true) - $start) / 1e6;
            $queryCount = count(DB::getQueryLog());
            $itemsReturned = count($result['items']);
            DB::disableQueryLog();
        }
        sort($timingsMs);
        $p95 = $timingsMs[(int) floor(0.95 * (count($timingsMs) - 1))];

        // --- EXPLAIN for the underlying spatial query ---
        $explainPosts = $this->explainNearbyPosts($viewer->location_coordinates, 10);
        $explainEvents = $this->explainNearbyPosts($viewer->location_coordinates, 50, Activity::class, 'start_time', 'published');
        $explainPostsDWithin = $this->explainDWithin($viewer->location_coordinates, 10);

        return [
            'items_returned' => $itemsReturned,
            'has_more' => $result['hasMore'] ? 'yes' : 'no',
            'timing_ms_min' => round($timingsMs[0], 2),
            'timing_ms_avg' => round(array_sum($timingsMs) / count($timingsMs), 2),
            'timing_ms_p95' => round($p95, 2),
            'feed_query_count' => $queryCount,
            'explain_posts' => $explainPosts,
            'explain_events' => $explainEvents,
            'explain_posts_dwithin' => $explainPostsDWithin,
        ];
    }

    /**
     * Same post query, but filtered with ST_DWithin on the geography column
     * instead of whereDistanceSphere — to check whether this predicate can
     * use the existing idx_posts_location GIST index.
     *
     * @return list<string>
     */
    private function explainDWithin(Point $location, int $radiusKm): array
    {
        $grammar = DB::connection()->getQueryGrammar();
        $point = $location->toSqlExpression(DB::connection())->getValue($grammar);
        $meters = $radiusKm * 1000;

        $sql = "EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT) SELECT * FROM posts WHERE status = 'active' AND ST_DWithin(location_coordinates, {$point}, {$meters}) ORDER BY ST_Distance(location_coordinates, {$point}) ASC LIMIT 30";

        $plan = DB::select($sql);

        return array_map(fn ($row) => $row->{'QUERY PLAN'}, $plan);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $authors
     * @return list<string>
     */
    private function seedPosts(int $count, $authors): array
    {
        $ids = [];
        $rows = [];
        $now = now();

        for ($i = 0; $i < $count; $i++) {
            if ($i % 5 === 0) {
                // 20% near the viewer's metro: 70% inside the 10km post
                // radius, 30% just outside it (up to ~40km away).
                $withinRadius = $i % 10 < 7;
                [$lat, $lng] = $this->pointAtDistance($withinRadius ? rand(0, 9000) : rand(12000, 40000));
            } else {
                // 80% scattered across distant metros — always outside the
                // viewer's radius, giving the dataset realistic selectivity.
                [$lat, $lng] = $this->pointNearOtherMetro();
            }

            $status = match (true) {
                $i % 50 === 0 => 'expired',
                $i % 80 === 0 => 'converted',
                default => 'active',
            };

            $id = (string) Str::uuid();
            $ids[] = $id;

            $rows[] = [
                'id' => $id,
                'user_id' => $authors->random()->id,
                'title' => 'Benchmark post #'.$i,
                'description' => 'Seeded by feed:benchmark',
                'location_coordinates' => (new Point($lat, $lng, 4326))->toSqlExpression(DB::connection()),
                'location_name' => 'Benchmark',
                'tags' => json_encode(['sports', 'social']),
                'expires_at' => $status === 'expired' ? $now->copy()->subHour() : $now->copy()->addDay(),
                'status' => $status,
                'view_count' => rand(0, 50),
                'reaction_count' => 0,
                'created_at' => $now->copy()->subMinutes(rand(0, 10000)),
                'updated_at' => $now,
            ];

            if (count($rows) >= 500) {
                DB::table('posts')->insert($rows);
                $rows = [];
            }
        }
        if ($rows !== []) {
            DB::table('posts')->insert($rows);
        }

        return $ids;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $authors
     * @return list<string>
     */
    private function seedEvents(int $count, $authors): array
    {
        $ids = [];
        $rows = [];
        $now = now();

        for ($i = 0; $i < $count; $i++) {
            if ($i % 5 === 0) {
                // 20% near the viewer's metro: 70% inside the 50km event
                // radius, 30% just outside it.
                $withinRadius = $i % 10 < 7;
                [$lat, $lng] = $this->pointAtDistance($withinRadius ? rand(0, 45000) : rand(55000, 100000));
            } else {
                // 80% scattered across distant metros.
                [$lat, $lng] = $this->pointNearOtherMetro();
            }

            $status = $i % 30 === 0 ? 'cancelled' : 'published';

            $id = (string) Str::uuid();
            $ids[] = $id;

            $rows[] = [
                'id' => $id,
                'host_id' => $authors->random()->id,
                'title' => 'Benchmark event #'.$i,
                'description' => 'Seeded by feed:benchmark',
                'activity_type' => 'social',
                'location_name' => 'Benchmark',
                'location_coordinates' => (new Point($lat, $lng, 4326))->toSqlExpression(DB::connection()),
                'start_time' => $now->copy()->addDays(rand(1, 20)),
                'max_attendees' => rand(5, 30),
                'current_attendees' => 0,
                'payment_type' => 'free',
                'currency' => 'USD',
                'is_public' => true,
                'requires_approval' => false,
                'images' => json_encode([]),
                'status' => $status,
                'created_at' => $now->copy()->subMinutes(rand(0, 10000)),
                'updated_at' => $now,
            ];

            if (count($rows) >= 500) {
                DB::table('activities')->insert($rows);
                $rows = [];
            }
        }
        if ($rows !== []) {
            DB::table('activities')->insert($rows);
        }

        return $ids;
    }

    /**
     * @param  list<string>  $postIds
     * @param  \Illuminate\Support\Collection<int, User>  $authors
     */
    private function seedReactions(array $postIds, $authors): void
    {
        $rows = [];
        foreach ($postIds as $i => $postId) {
            if ($i % 3 !== 0) {
                continue; // ~30% of posts get reactions
            }
            $reactionCount = rand(1, 20);
            foreach ($authors->random(min($reactionCount, $authors->count())) as $user) {
                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'post_id' => $postId,
                    'user_id' => $user->id,
                    'reaction_type' => 'im_down',
                    'created_at' => now(),
                ];
            }
            if (count($rows) >= 500) {
                PostReaction::query()->insert($rows);
                $rows = [];
            }
        }
        if ($rows !== []) {
            PostReaction::query()->insert($rows);
        }
    }

    /**
     * @param  list<string>  $eventIds
     * @param  \Illuminate\Support\Collection<int, User>  $authors
     */
    private function seedRsvps(array $eventIds, $authors): void
    {
        $rows = [];
        foreach ($eventIds as $i => $eventId) {
            if ($i % 3 !== 0) {
                continue;
            }
            $rsvpCount = rand(1, 15);
            foreach ($authors->random(min($rsvpCount, $authors->count())) as $user) {
                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'activity_id' => $eventId,
                    'user_id' => $user->id,
                    'status' => 'attending',
                    'is_paid' => false,
                    'attended' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (count($rows) >= 500) {
                Rsvp::query()->insert($rows);
                $rows = [];
            }
        }
        if ($rows !== []) {
            Rsvp::query()->insert($rows);
        }
    }

    /**
     * A random point roughly $meters away from the fixed centre.
     *
     * @return array{0: float, 1: float}
     */
    private function pointAtDistance(int $meters): array
    {
        $bearing = deg2rad(rand(0, 359));
        $deltaLat = ($meters * cos($bearing)) / 111_320;
        $deltaLng = ($meters * sin($bearing)) / (111_320 * cos(deg2rad(self::CENTER_LAT)));

        return [self::CENTER_LAT + $deltaLat, self::CENTER_LNG + $deltaLng];
    }

    /**
     * A random point within ~20km of one of the other, distant metros.
     *
     * @return array{0: float, 1: float}
     */
    private function pointNearOtherMetro(): array
    {
        [$lat, $lng] = self::OTHER_METROS[array_rand(self::OTHER_METROS)];
        $bearing = deg2rad(rand(0, 359));
        $meters = rand(0, 20000);
        $deltaLat = ($meters * cos($bearing)) / 111_320;
        $deltaLng = ($meters * sin($bearing)) / (111_320 * cos(deg2rad($lat)));

        return [$lat + $deltaLat, $lng + $deltaLng];
    }

    /**
     * @return list<string>
     */
    private function explainNearbyPosts(Point $location, int $radiusKm, string $model = Post::class, string $orderColumn = 'created_at', string $status = 'active'): array
    {
        $query = $model::query()
            ->where('status', $status)
            ->whereDistanceSphere('location_coordinates', $location, '<=', $radiusKm * 1000)
            ->orderByDistanceSphere('location_coordinates', $location, 'asc')
            ->limit(30);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $plan = DB::select('EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT) '.$sql, $bindings);

        return array_map(fn ($row) => $row->{'QUERY PLAN'}, $plan);
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    private function printSummary(array $results): void
    {
        $this->newLine();
        $this->table(
            ['scale', 'items', 'has more', 'min ms', 'avg ms', 'p95 ms', 'feed queries'],
            collect($results)->map(fn ($r, $scale) => [
                $scale,
                $r['items_returned'],
                $r['has_more'],
                $r['timing_ms_min'],
                $r['timing_ms_avg'],
                $r['timing_ms_p95'],
                $r['feed_query_count'],
            ])->toArray()
        );

        foreach ($results as $scale => $r) {
            $this->newLine();
            $this->line("<comment>EXPLAIN — posts spatial query at scale {$scale}:</comment>");
            $this->line(implode("\n", $r['explain_posts']));
            $this->newLine();
            $this->line("<comment>EXPLAIN — events spatial query at scale {$scale}:</comment>");
            $this->line(implode("\n", $r['explain_events']));
            $this->newLine();
            $this->line("<comment>EXPLAIN — posts via ST_DWithin at scale {$scale}:</comment>");
            $this->line(implode("\n", $r['explain_posts_dwithin']));
        }
    }
}
