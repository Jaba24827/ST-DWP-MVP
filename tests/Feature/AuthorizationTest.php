<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    public function test_staff_cannot_download_restricted_document(): void
    {
        $staff = User::where(
            'email',
            'r.jeanbaptiste@stlukehaiti.org'
        )->firstOrFail();

        $doc = Document::where(
            'classification',
            'restricted'
        )->firstOrFail();

        $this->actingAs($staff)
            ->withSession([
                'mfa.verified' => true,
            ])
            ->get(route('documents.download', $doc))
            ->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'actor' => $staff->email,
            'action' => 'documents.open',
            'result' => 'denied',
        ]);
    }

    public function test_restricted_documents_are_excluded_from_staff_listing(): void
    {
        $staff = User::where(
            'email',
            'r.jeanbaptiste@stlukehaiti.org'
        )->firstOrFail();

        $classifications = Document::visibleTo($staff)
            ->pluck('classification')
            ->unique()
            ->toArray();

        $this->assertNotContains(
            'restricted',
            $classifications
        );
    }

    public function test_administrator_cannot_remove_roles_manage_from_own_role(): void
    {
        $admin = User::where(
            'email',
            'j.dorsainvil@stlukehaiti.org'
        )->firstOrFail();

        $this->actingAs($admin)
            ->withSession([
                'mfa.verified' => true,
            ])
            ->patch(
                route('roles.update', $admin->role_id),
                [
                    'permissions' => [1, 2],
                ]
            )
            ->assertSessionHasErrors('permissions');
    }

    public function test_password_only_session_cannot_reach_dashboard(): void
    {
        $user = User::firstOrFail();

        $this->actingAs($user)
            ->withSession([
                'mfa.verified' => false,
            ])
            ->get(route('dashboard'))
            ->assertRedirect(route('mfa.show'));
    }

    public function test_password_must_meet_security_policy(): void
    {
        $user = User::where(
            'email',
            'j.dorsainvil@stlukehaiti.org'
        )->firstOrFail();

        $this->actingAs($user)
            ->withSession([
                'mfa.verified' => true,
            ])
            ->put(
                route('account.password.update'),
                [
                    'current_password' => 'Demo!Passw0rd2026',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ]
            )
            ->assertSessionHasErrors('password');
    }
}