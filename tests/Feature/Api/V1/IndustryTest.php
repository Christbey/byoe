<?php

use App\Models\Industry;
use App\Models\IndustrySkill;
use App\Models\IndustryTemplate;

test('anyone can list industries via API', function () {
    Industry::factory()->count(3)->create(['is_active' => true]);

    $response = $this->getJson('/api/v1/industries');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug', 'is_active'],
            ],
        ]);

    expect(count($response->json('data')))->toBe(3);
});

test('only active industries are listed', function () {
    Industry::factory()->create(['is_active' => true, 'name' => 'Active Industry']);
    Industry::factory()->create(['is_active' => false, 'name' => 'Inactive Industry']);

    $response = $this->getJson('/api/v1/industries');

    $data = $response->json('data');
    expect(count($data))->toBe(1)
        ->and($data[0]['name'])->toBe('Active Industry');
});

test('anyone can view industry with skills and templates via API', function () {
    $industry = Industry::factory()->create(['is_active' => true]);
    IndustrySkill::factory()->count(2)->create(['industry_id' => $industry->id]);
    IndustryTemplate::factory()->count(2)->create(['industry_id' => $industry->id]);

    $response = $this->getJson("/api/v1/industries/{$industry->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'skills' => ['*' => ['id', 'name']],
                'templates' => ['*' => ['id', 'title']],
            ],
        ]);

    expect(count($response->json('data.skills')))->toBe(2)
        ->and(count($response->json('data.templates')))->toBe(2);
});

test('industries are sorted by sort order', function () {
    Industry::factory()->create(['is_active' => true, 'name' => 'C', 'sort_order' => 3]);
    Industry::factory()->create(['is_active' => true, 'name' => 'A', 'sort_order' => 1]);
    Industry::factory()->create(['is_active' => true, 'name' => 'B', 'sort_order' => 2]);

    $response = $this->getJson('/api/v1/industries');

    $names = collect($response->json('data'))->pluck('name')->toArray();
    expect($names)->toBe(['A', 'B', 'C']);
});
