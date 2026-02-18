<?php

use App\Models\Industry;
use App\Models\IndustrySkill;
use App\Models\IndustryTemplate;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeShopOwnerWithIndustry(): array
{
    $industry = Industry::create([
        'name' => 'Coffee Shop',
        'slug' => 'coffee-shop',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    IndustrySkill::create(['industry_id' => $industry->id, 'name' => 'Barista', 'sort_order' => 1]);
    IndustrySkill::create(['industry_id' => $industry->id, 'name' => 'Espresso', 'sort_order' => 2]);

    IndustryTemplate::create([
        'industry_id' => $industry->id,
        'title' => 'Morning Barista',
        'description' => 'A morning shift',
        'skills' => ['Barista', 'Espresso'],
        'sort_order' => 1,
    ]);

    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id, 'industry_id' => $industry->id]);

    return [$user, $shop, $industry];
}

test('edit profile page passes industries to view', function () {
    [$user] = makeShopOwnerWithIndustry();

    $response = $this->actingAs($user)->get('/shop/profile/edit');

    $response->assertInertia(fn ($page) => $page
        ->component('shop/EditProfile')
        ->has('industries', 1)
        ->where('industries.0.name', 'Coffee Shop')
    );
});

test('shop owner can update industry', function () {
    [$user, $shop, $industry] = makeShopOwnerWithIndustry();

    $newIndustry = Industry::create([
        'name' => 'Restaurant',
        'slug' => 'restaurant',
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $response = $this->actingAs($user)->put('/shop/profile', [
        'name' => $shop->name,
        'industry_id' => $newIndustry->id,
    ]);

    $response->assertRedirect('/shop/profile');

    $shop->refresh();
    expect($shop->industry_id)->toBe($newIndustry->id);
});

test('shop owner can save custom skills', function () {
    [$user, $shop] = makeShopOwnerWithIndustry();

    $response = $this->actingAs($user)->put('/shop/profile', [
        'name' => $shop->name,
        'custom_skills' => ['Spanish-speaking', 'Bilingual'],
    ]);

    $response->assertRedirect('/shop/profile');

    $shop->refresh();
    expect($shop->custom_skills)->toEqual(['Spanish-speaking', 'Bilingual']);
});

test('create service request page passes industry skills merged with custom skills', function () {
    [$user, $shop, $industry] = makeShopOwnerWithIndustry();

    // Add a custom skill
    $shop->update(['custom_skills' => ['Spanish-speaking']]);
    $shop->load('locations');

    // Create a location so the create page doesn't redirect
    \App\Models\ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user)->get('/shop/service-requests/create');

    $response->assertInertia(fn ($page) => $page
        ->component('shop/CreateServiceRequest')
        ->has('skills', 3) // Barista + Espresso + Spanish-speaking
        ->has('templates', 1)
    );
});
