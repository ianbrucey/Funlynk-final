<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface SearchServiceInterface
{
    /**
     * Search posts and events by text query
     *
     * @param  string  $query  Search text
     * @param  User  $user  Current user for geo context
     * @param  int|null  $radius  Optional geo filter (km)
     * @param  string  $contentType  'all', 'posts', 'events'
     * @return Collection Mixed results with ['type' => 'post'|'event', 'data' => Model]
     */
    public function search(
        string $query,
        User $user,
        ?int $radius = null,
        string $contentType = 'all'
    ): Collection;
}

