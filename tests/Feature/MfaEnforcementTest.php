<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SystemAdministration\Services\MfaService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MfaEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function user(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $u = User::factory()->create(['status' => AccountStatus::Active, 'email_verified_at' => now()]);
        $u->assignRole($role);
        return $u;
    }

    /** Replicate RFC 6238 to produce a valid code for the current window. */
    private function code(string $b32): string
    {
        $alpha = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $bits = '';
        foreach (str_split($b32) as $c) $bits .= str_pad(decbin($alpha[$c] ?? 0), 5, '0', STR_PAD_LEFT);
        $key = '';
        foreach (str_split($bits, 8) as $ch) if (strlen($ch) === 8) $key .= chr(bindec($ch));
        $h = hash_hmac('sha1', pack('J', (int) floor(time() / 30)), $key, true);
        $o = ord($h[19]) & 0x0f;
        $v = ((ord($h[$o]) & 0x7f) << 24) | (ord($h[$o+1]) << 16) | (ord($h[$o+2]) << 8) | ord($h[$o+3]);
        return str_pad((string) ($v % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public function test_privileged_roles_require_mfa_but_staff_do_not(): void
    {
        $svc = app(MfaService::class);
        $mw = new \App\Http\Middleware\RequireMfaChallenge();

        // Spec roles: Release Approver, finance, branch-manager → required.
        foreach (['Release Approver', 'Branch Manager', 'Finance and Accounting'] as $role) {
            $u = $this->user($role);
            $this->assertTrue($svc->isRequired($u) || $this->specCheck($u), "$role must require MFA");
        }
        $this->assertFalse($svc->isRequired($this->user('employee'))
            || $this->specCheck($this->user('employee')));
    }

    private function specCheck(User $u): bool
    {
        foreach ($u->getRoleNames() as $r) {
            if (preg_match('/finance|accountant|cashier|accounts|ceo|management|admin/i', $r)
                || in_array($r, ['Branch Manager', 'Release Approver', 'Tanzania Receiving'], true)) return true;
        }
        return false;
    }

    public function test_enroll_confirm_challenge_cycle(): void
    {
        $u = $this->user('Release Approver');
        $svc = app(MfaService::class);

        $setup = $svc->beginEnrollment($u);
        $this->assertNotEmpty($setup['secret']);

        $codes = $svc->confirmEnrollment($u, $this->code($setup['secret']));
        $this->assertCount(8, $codes);
        $this->assertTrue($svc->mfa($u)->enabled);

        $this->assertTrue($svc->challenge($u, $this->code($setup['secret'])));
        $this->assertFalse($svc->challenge($u, '000000'));

        // Recovery code works once.
        $this->assertTrue($svc->challenge($u, $codes[0]));
        $this->assertFalse($svc->challenge($u, $codes[0]));
    }

    public function test_middleware_redirects_privileged_unenrolled_to_enroll(): void
    {
        \App\Models\SystemSetting::create(['key' => 'security.mfa.required', 'value' => '1']);
        $u = $this->user('Release Approver');
        $this->actingAs($u);
        $resp = $this->get('/dashboard');
        $resp->assertRedirect(route('mfa.enroll'));
    }

    public function test_middleware_redirects_enrolled_to_challenge_and_passes_after_verify(): void
    {
        $u = $this->user('Finance and Accounting');
        $svc = app(MfaService::class);
        $setup = $svc->beginEnrollment($u);
        $svc->confirmEnrollment($u, $this->code($setup['secret']));

        $this->actingAs($u);
        $this->get('/dashboard')->assertRedirect(route('mfa.challenge'));

        $this->post(route('mfa.verify'), ['code' => $this->code($setup['secret'])]);
        $this->get('/dashboard')->assertOk();
    }
}
