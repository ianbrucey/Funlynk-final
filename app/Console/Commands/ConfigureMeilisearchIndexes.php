<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use Illuminate\Console\Command;
use MeiliSearch\Client;

class ConfigureMeilisearchIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meilisearch:configure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure Meilisearch indexes with settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $client = new Client(
            config('scout.meilisearch.host'),
            config('scout.meilisearch.key')
        );
        
        $this->info('Configuring Posts index...');
        
        $postsIndex = $client->index((new Post())->searchableAs());
        $postsIndex->updateSettings([
            'filterableAttributes' => ['status', 'expires_at', 'created_at', '_geo'],
            'sortableAttributes' => ['created_at', 'expires_at', '_geo'],
            'searchableAttributes' => ['title', 'description', 'tags'],
            'rankingRules' => [
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
            ],
            'typoTolerance' => [
                'enabled' => true,
                'minWordSizeForTypos' => [
                    'oneTypo' => 4,  // Allow 1 typo for words with 4+ characters
                    'twoTypos' => 8, // Allow 2 typos for words with 8+ characters
                ],
            ],
            'synonyms' => [
                'basketball-bball-hoops' => ['basketball', 'bball', 'hoops'],
                'soccer-football-futbol' => ['soccer', 'football', 'futbol'],
                'volleyball-vball' => ['volleyball', 'vball'],
                'running-jogging' => ['running', 'jogging'],
                'cycling-biking' => ['cycling', 'biking'],
                'hiking-trekking' => ['hiking', 'trekking'],
            ],
        ]);
        
        $this->info('Posts index configured with typo tolerance and synonyms');
        
        $this->info('Configuring Activities index...');
        
        $activitiesIndex = $client->index((new Activity())->searchableAs());
        $activitiesIndex->updateSettings([
            'filterableAttributes' => ['status', 'start_time', 'created_at', '_geo'],
            'sortableAttributes' => ['created_at', 'start_time', '_geo'],
            'searchableAttributes' => ['title', 'description', 'tags'],
            'rankingRules' => [
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
            ],
            'typoTolerance' => [
                'enabled' => true,
                'minWordSizeForTypos' => [
                    'oneTypo' => 4,
                    'twoTypos' => 8,
                ],
            ],
            'synonyms' => [
                'basketball-bball-hoops' => ['basketball', 'bball', 'hoops'],
                'soccer-football-futbol' => ['soccer', 'football', 'futbol'],
                'volleyball-vball' => ['volleyball', 'vball'],
                'running-jogging' => ['running', 'jogging'],
                'cycling-biking' => ['cycling', 'biking'],
                'hiking-trekking' => ['hiking', 'trekking'],
            ],
        ]);
        
        $this->info('Activities index configured with typo tolerance and synonyms');
        
        $this->info('Configuring Users index...');
        
        $usersIndex = $client->index((new User())->searchableAs());
        $usersIndex->updateSettings([
            'filterableAttributes' => ['id', 'is_active', 'interests', 'created_at', '_geo'],
            'sortableAttributes' => ['follower_count', 'created_at', '_geo'],
            'searchableAttributes' => ['username', 'display_name', 'bio', 'interests'],
            'rankingRules' => [
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
            ],
            'typoTolerance' => [
                'enabled' => true,
                'minWordSizeForTypos' => [
                    'oneTypo' => 3,  // Allow 1 typo for words with 3+ characters (names are shorter)
                    'twoTypos' => 6, // Allow 2 typos for words with 6+ characters
                ],
            ],
        ]);
        
        $this->info('Users index configured with typo tolerance');
        
        $this->info('✅ All Meilisearch indexes configured!');
        
        return Command::SUCCESS;
    }
}
