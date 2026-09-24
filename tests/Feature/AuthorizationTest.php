<?php

use App\Models\Document;
use App\Models\User;

/**
 * The tests that matter most for this MVP are the refusals. A passing
 * happy path proves a feature exists; a passing denial proves the control
 * cannot be walked around.
 */

it('refuses a restricted document to a staff role and records the attempt', function () {
    $staff = User::where('email', 'r.jeanbaptiste@stlukehaiti.org')->firstOrFail();
    $doc   = Document::where('classification', 'restricted')->firstOrFail();

    $this->actingAs($staff)->withSession(['mfa.verified' => true])
        ->get(route('documents.download', $doc))
        ->assertForbidden();

    $this->assertDatabaseHas('audit_logs', [
        'actor'  => $staff->email,
        'action' => 'documents.open',
        'result' => 'denied',
    ]);
});

it('keeps restricted rows out of the listing query entirely', function () {
    $staff = User::where('email', 'r.jeanbaptiste@stlukehaiti.org')->firstOrFail();

    expect(Document::visibleTo($staff)->pluck('classification')->unique())
        ->not->toContain('restricted');
});

it('will not let an administrator remove roles.manage from their own role', function () {
    $admin = User::where('email', 'j.dorsainvil@stlukehaiti.org')->firstOrFail();

    $this->actingAs($admin)->withSession(['mfa.verified' => true])
        ->patch(route('roles.update', $admin->role_id), ['permissions' => [1, 2]])
        ->assertSessionHasErrors('permissions');
});

it('stops a password-only session from reaching the dashboard', function () {
    $user = User::first();

    $this->actingAs($user)->withSession(['mfa.verified' => false])
        ->get(route('dashboard'))
        ->assertRedirect(route('mfa.show'));
});

it('refuses a password that does not meet the policy', function () {
    $user = User::where('email', 'j.dorsainvil@stlukehaiti.org')->firstOrFail();

    $this->actingAs($user)->withSession(['mfa.verified' => true])
        ->put(route('account.password.update'), [
            'current_password'      => 'Demo!Passw0rd2026',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasErrors('password');
});
