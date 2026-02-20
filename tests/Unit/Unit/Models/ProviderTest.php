<?php

use App\Models\Industry;
use App\Models\IndustrySkill;
use App\Models\IndustryTemplate;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('provider belongs to industry', function () {
    $industry = Industry::create([
        'name' => 'Coffee Shop',
        'slug' => 'coffee-shop',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $user = User::factory()->create();
    $provider = Provider::factory()->create([
        'user_id' => $user->id,
        'industry_id' => $industry->id,
    ]);

    expect($provider->industry)->toBeInstanceOf(Industry::class)
        ->and($provider->industry->name)->toBe('Coffee Shop');
});

test('provider stores skills as json array', function () {
    $user = User::factory()->create();
    $provider = Provider::factory()->create([
        'user_id' => $user->id,
        'skills' => ['espresso', 'latte_art'],
    ]);

    $provider->refresh();
    expect($provider->skills)->toBeArray()->toEqual(['espresso', 'latte_art']);
});

test('provider availableSkills merges industry skills with provider skills', function () {
    $industry = Industry::create([
        'name' => 'Coffee Shop',
        'slug' => 'coffee-shop',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    IndustrySkill::create(['industry_id' => $industry->id, 'name' => 'Barista', 'sort_order' => 1]);
    IndustrySkill::create(['industry_id' => $industry->id, 'name' => 'Espresso', 'sort_order' => 2]);

    $user = User::factory()->create();
    $provider = Provider::factory()->create([
        'user_id' => $user->id,
        'industry_id' => $industry->id,
        'skills' => ['latte_art', 'customer_service'],
    ]);

    $availableSkills = $provider->availableSkills();

    expect($availableSkills)->toBeArray()
        ->toHaveCount(4)
        ->toContain('Barista', 'Espresso', 'latte_art', 'customer_service');
});

test('provider availableSkills deduplicates skills', function () {
    $industry = Industry::create([
        'name' => 'Coffee Shop',
        'slug' => 'coffee-shop',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    IndustrySkill::create(['industry_id' => $industry->id, 'name' => 'Barista', 'sort_order' => 1]);
    IndustrySkill::create(['industry_id' => $industry->id, 'name' => 'Espresso', 'sort_order' => 2]);

    $user = User::factory()->create();
    $provider = Provider::factory()->create([
        'user_id' => $user->id,
        'industry_id' => $industry->id,
        'skills' => ['Barista', 'latte_art'], // 'Barista' duplicates industry skill
    ]);

    $availableSkills = $provider->availableSkills();

    expect($availableSkills)->toBeArray()
        ->toHaveCount(3)
        ->toContain('Barista', 'Espresso', 'latte_art');
});

test('provider availableSkills returns only industry skills when provider has no skills', function () {
    $industry = Industry::create([
        'name' => 'Coffee Shop',
        'slug' => 'coffee-shop',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    IndustrySkill::create(['industry_id' => $industry->id, 'name' => 'Barista', 'sort_order' => 1]);
    IndustrySkill::create(['industry_id' => $industry->id, 'name' => 'Espresso', 'sort_order' => 2]);

    $user = User::factory()->create();
    $provider = Provider::factory()->create([
        'user_id' => $user->id,
        'industry_id' => $industry->id,
        'skills' => [],
    ]);

    $availableSkills = $provider->availableSkills();

    expect($availableSkills)->toBeArray()
        ->toHaveCount(2)
        ->toBe(['Barista', 'Espresso']);
});

test('provider availableSkills returns empty array when no industry and no skills', function () {
    $user = User::factory()->create();
    $provider = Provider::factory()->create([
        'user_id' => $user->id,
        'industry_id' => null,
        'skills' => [],
    ]);

    $availableSkills = $provider->availableSkills();

    expect($availableSkills)->toBeArray()->toBeEmpty();
});

test('provider availableTemplates returns industry templates', function () {
    $industry = Industry::create([
        'name' => 'Coffee Shop',
        'slug' => 'coffee-shop',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    IndustryTemplate::create([
        'industry_id' => $industry->id,
        'title' => 'Morning Barista',
        'description' => 'A morning shift',
        'skills' => ['Barista', 'Espresso'],
        'sort_order' => 1,
    ]);

    $user = User::factory()->create();
    $provider = Provider::factory()->create([
        'user_id' => $user->id,
        'industry_id' => $industry->id,
    ]);

    $templates = $provider->availableTemplates();

    expect($templates)->toBeArray()
        ->toHaveCount(1)
        ->and($templates[0]['title'])->toBe('Morning Barista');
});

test('provider profileCompleteness includes industry_selected step', function () {
    $industry = Industry::create([
        'name' => 'Coffee Shop',
        'slug' => 'coffee-shop',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $user = User::factory()->create();
    $provider = Provider::factory()->create([
        'user_id' => $user->id,
        'industry_id' => $industry->id,
        'bio' => 'Test bio',
        'skills' => ['espresso'],
    ]);

    $completeness = $provider->profileCompleteness();

    expect($completeness)->toBeArray()
        ->toHaveKey('steps')
        ->and($completeness['steps'])->toHaveKey('industry_selected')
        ->and($completeness['steps']['industry_selected'])->toBeTrue();
});

test('provider profileCompleteness shows industry_selected as incomplete when no industry', function () {
    $user = User::factory()->create();
    $provider = Provider::factory()->create([
        'user_id' => $user->id,
        'industry_id' => null,
    ]);

    $completeness = $provider->profileCompleteness();

    expect($completeness['steps']['industry_selected'])->toBeFalse();
});
