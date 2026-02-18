<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    /** @use HasFactory<\Database\Factories\ShopFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'phone',
        'website',
        'operating_hours',
        'status',
        'industry_id',
        'custom_skills',
        'ein',
        'stripe_customer_id',
        'stripe_payment_method_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'operating_hours' => 'array',
            'custom_skills' => 'array',
            'ein' => 'encrypted',
        ];
    }

    /**
     * Get the user that owns the shop.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shop's industry.
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    /**
     * Get the shop's locations.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(ShopLocation::class);
    }

    /**
     * Get the shop's primary location.
     */
    public function primaryLocation()
    {
        return $this->hasOne(ShopLocation::class)->where('is_primary', true);
    }

    /**
     * Get the shop's service requests.
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasManyThrough(ServiceRequest::class, ShopLocation::class);
    }

    /**
     * Get the shop's payments.
     */
    public function payments(): HasMany
    {
        return $this->hasManyThrough(Payment::class, ServiceRequest::class);
    }
}
