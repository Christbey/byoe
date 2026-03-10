<?php

use App\Models\AuditLog;
use App\Models\Provider;
use App\Models\User;

test('admin can review provider trust status and audit the change', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $provider = Provider::factory()->needsReview()->create([
        'trust_notes' => null,
        'trust_score' => 52,
        'reliability_score' => 78,
    ]);

    $response = $this->actingAs($admin)->post("/admin/providers/{$provider->id}/review", [
        'vetting_status' => 'approved',
        'background_check_status' => 'clear',
        'trust_notes' => 'Identity and compliance review completed.',
    ]);

    $response->assertRedirect('/admin/providers');

    $provider->refresh();

    expect($provider->vetting_status)->toBe('approved')
        ->and($provider->background_check_status)->toBe('clear')
        ->and($provider->trust_notes)->toBe('Identity and compliance review completed.')
        ->and($provider->last_reviewed_by_user_id)->toBe($admin->id)
        ->and($provider->is_active)->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => 'provider_trust_reviewed',
        'auditable_type' => Provider::class,
        'auditable_id' => $provider->id,
    ]);

    $auditLog = AuditLog::latest()->first();

    expect($auditLog->new_values['vetting_status'])->toBe('approved')
        ->and($auditLog->new_values['background_check_status'])->toBe('clear');
});
