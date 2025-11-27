<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostConversion extends Model
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
            'trigger_type' => 'string',
            'reactions_at_conversion' => 'integer',
            'comments_at_conversion' => 'integer',
            'views_at_conversion' => 'integer',
            'rsvp_conversion_rate' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'event_id');
    }

    // Alias for backward compatibility
    public function event(): BelongsTo
    {
        return $this->activity();
    }
}
