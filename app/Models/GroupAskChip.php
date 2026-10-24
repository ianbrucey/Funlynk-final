<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * A member's self-reported "I chipped in". Never counted in totals (spec 015).
 */
class GroupAskChip extends Model
{
    use HasUuids;

    protected $fillable = ['group_ask_id', 'user_id'];
}
