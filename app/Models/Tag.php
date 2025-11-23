<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false; // created_at only

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'usage_count' => 'integer',
            'is_featured' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    // Relationships
    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'activity_tag');
    }
}
