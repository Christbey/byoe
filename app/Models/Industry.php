<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Industry extends Model
{
    protected $fillable = ['name', 'slug', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function skills(): HasMany
    {
        return $this->hasMany(IndustrySkill::class)->orderBy('sort_order');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(IndustryTemplate::class)->orderBy('sort_order');
    }

    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class);
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }
}
