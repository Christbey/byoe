<?php

use App\Models\Industry;
use App\Models\IndustrySkill;
use App\Models\IndustryTemplate;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('industry has many skills ordered by sort_order', function () {
    $industry = Industry::create([
        'name' => 'Test Industry',
        'slug' => 'test-industry',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    IndustrySkill::create(['industry_id' => $industry->id, 'name' => 'Skill C', 'sort_order' => 3]);
    IndustrySkill::create(['industry_id' => $industry->id, 'name' => 'Skill A', 'sort_order' => 1]);
    IndustrySkill::create(['industry_id' => $industry->id, 'name' => 'Skill B', 'sort_order' => 2]);

    $skills = $industry->skills;

    expect($skills)->toHaveCount(3)
        ->and($skills->pluck('name')->toArray())->toBe(['Skill A', 'Skill B', 'Skill C']);
});

test('industry has many templates ordered by sort_order', function () {
    $industry = Industry::create([
        'name' => 'Test Industry',
        'slug' => 'test-industry',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    IndustryTemplate::create([
        'industry_id' => $industry->id,
        'title' => 'Template B',
        'description' => 'Second template',
        'skills' => ['Skill A'],
        'sort_order' => 2,
    ]);
    IndustryTemplate::create([
        'industry_id' => $industry->id,
        'title' => 'Template A',
        'description' => 'First template',
        'skills' => ['Skill B'],
        'sort_order' => 1,
    ]);

    $templates = $industry->templates;

    expect($templates)->toHaveCount(2)
        ->and($templates->first()->title)->toBe('Template A');
});

test('industry skill has no timestamps', function () {
    $skill = new IndustrySkill;
    expect($skill->timestamps)->toBeFalse();
});

test('industry template casts skills to array', function () {
    $industry = Industry::create([
        'name' => 'Test Industry',
        'slug' => 'test-industry',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $template = IndustryTemplate::create([
        'industry_id' => $industry->id,
        'title' => 'Test Template',
        'description' => 'A template',
        'skills' => ['Skill A', 'Skill B'],
        'sort_order' => 1,
    ]);

    expect($template->skills)->toBeArray()->toEqual(['Skill A', 'Skill B']);
});

test('shop belongs to industry', function () {
    $industry = Industry::create([
        'name' => 'Coffee Shop',
        'slug' => 'coffee-shop',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $user = User::factory()->create();
    $shop = Shop::factory()->create([
        'user_id' => $user->id,
        'industry_id' => $industry->id,
    ]);

    expect($shop->industry)->toBeInstanceOf(Industry::class)
        ->and($shop->industry->name)->toBe('Coffee Shop');
});

test('shop stores custom skills as json array', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create([
        'user_id' => $user->id,
        'industry_id' => null,
        'custom_skills' => ['Spanish-speaking', 'Bilingual'],
    ]);

    $shop->refresh();
    expect($shop->custom_skills)->toBeArray()->toEqual(['Spanish-speaking', 'Bilingual']);
});
