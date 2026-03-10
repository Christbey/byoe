<?php

use App\Mail\Admin\ProviderRiskDigest;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('provider risk alert command emails admins when risky providers exist', function () {
    Mail::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Provider::factory()->approved()->create([
        'trust_score' => 48,
        'reliability_score' => 62,
        'dispute_count' => 2,
        'no_show_count' => 1,
    ]);

    $this->artisan('emails:send-provider-risk-alerts')
        ->expectsOutput('Sent 1 provider risk digest email(s).')
        ->assertSuccessful();

    Mail::assertQueued(ProviderRiskDigest::class, function (ProviderRiskDigest $mail) use ($admin) {
        return $mail->hasTo($admin->email) && $mail->providers->count() === 1;
    });
});
