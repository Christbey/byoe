<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustrySkill extends Model
{
    public $timestamps = false;

    protected $fillable = ['industry_id', 'name', 'sort_order'];

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }
}
