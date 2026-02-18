<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryTemplate extends Model
{
    protected $fillable = ['industry_id', 'title', 'description', 'skills', 'sort_order'];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }
}
