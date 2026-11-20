<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_flagged_user_is_redirected_to_change_screen(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('password.force'));
    }

    public function test_submitting_new_password_clears_flag_and_allows_access(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->post('/force-password-change', [
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertRedirect(route('onboarding'));

        $user->refresh();
        $this->assertFalse((bool) $user->must_change_password);
        $this->assertTrue(Hash::check('NewPass123', $user->password));
    }

    public function test_weak_password_is_rejected(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->post('/force-password-change', [
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertSessionHasErrors('password');

        $this->assertTrue((bool) $user->fresh()->must_change_password);
    }
}
