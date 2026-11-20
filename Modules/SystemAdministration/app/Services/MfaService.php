<?php

namespace Modules\SystemAdministration\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\SystemAdministration\Models\UserMfa;

/**
 * Platform MFA — real TOTP (RFC 6238, HMAC-SHA1, 30s window, ±1 step)
 * with hashed one-time recovery codes. Secrets encrypted via cast.
 */
class MfaService
{
    private const PERIOD = 30;
    private const DIGITS = 6;

    public function isRequired(User $user): bool
    {
        // Super admin and privileged tiers: ALWAYS required — no scope can weaken.
        if ($user->hasAnyRole(['super-admin', 'group-administrator', 'company-administrator'])) {
            return true;
        }
        $default = SettingCatalog::definitions()['security.mfa.required']['default'] ?? false;
        return (bool) $default
            || \App\Models\SystemSetting::where('key', 'security.mfa.required')->value('value') === '1';
    }

    public function mfa(User $user): ?UserMfa
    {
        return UserMfa::where('user_id', $user->id)->first();
    }

    /** Begin enrollment — secret issued but NOT enabled until verified. */
    public function beginEnrollment(User $user): array
    {
        $secret = $this->base32(random_bytes(20));
        $mfa = UserMfa::updateOrCreate(['user_id' => $user->id],
            ['secret' => $secret, 'enabled' => false, 'verified_at' => null]);
        $uri = 'otpauth://totp/XerinERP:' . urlencode($user->email)
            . '?secret=' . $secret . '&issuer=XerinERP';
        return ['secret' => $secret, 'provisioning_uri' => $uri];
    }

    /** Verify a TOTP code → activates MFA + issues hashed recovery codes. */
    public function confirmEnrollment(User $user, string $code): array
    {
        $mfa = $this->mfa($user);
        abort_unless($mfa && ! $mfa->enabled, 422, 'No pending enrollment.');
        if (! $this->verify($mfa->secret, $code)) {
            throw ValidationException::withMessages(['code' => 'Invalid authenticator code.']);
        }
        $codes = collect(range(1, 8))->map(fn () => strtoupper(Str::random(10)))->all();
        $mfa->update(['enabled' => true, 'verified_at' => now(),
            'recovery_hashes' => array_map(fn ($c) => Hash::make($c), $codes)]);
        return $codes; // shown once, never retrievable again
    }

    /** Login challenge: TOTP code OR single-use recovery code. */
    public function challenge(User $user, string $code): bool
    {
        $mfa = $this->mfa($user);
        if (! $mfa || ! $mfa->enabled) return false;
        if ($this->verify($mfa->secret, $code)) return true;
        foreach ($mfa->recovery_hashes ?? [] as $i => $hash) {
            if (Hash::check(strtoupper($code), $hash)) {
                $remaining = $mfa->recovery_hashes;
                unset($remaining[$i]);
                $mfa->update(['recovery_hashes' => array_values($remaining)]);
                return true;
            }
        }
        return false;
    }

    /** RFC 6238 TOTP verify, ±1 period tolerance. */
    public function verify(string $secret, string $code): bool
    {
        $counter = (int) floor(time() / self::PERIOD);
        foreach ([-1, 0, 1] as $drift) {
            if (hash_equals($this->totp($secret, $counter + $drift), $code)) return true;
        }
        return false;
    }

    private function totp(string $base32Secret, int $counter): string
    {
        $key = $this->base32Decode($base32Secret);
        $msg = pack('J', $counter); // 64-bit big-endian
        $hash = hash_hmac('sha1', $msg, $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $bin = (ord($hash[$offset]) & 0x7f) << 24 | ord($hash[$offset+1]) << 16
             | ord($hash[$offset+2]) << 8 | ord($hash[$offset+3]);
        return str_pad((string) ($bin % 10 ** self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $out = ''; $bits = 0; $buffer = 0;
        foreach (str_split($data) as $ch) {
            $buffer = ($buffer << 8) | ord($ch); $bits += 8;
            while ($bits >= 5) { $bits -= 5; $out .= $alphabet[($buffer >> $bits) & 31]; }
        }
        if ($bits > 0) $out .= $alphabet[($buffer << (5 - $bits)) & 31];
        return $out;
    }

    private function base32Decode(string $b32): string
    {
        $alphabet = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $out = ''; $bits = 0; $buffer = 0;
        foreach (str_split(rtrim(strtoupper($b32), '=')) as $ch) {
            if (! isset($alphabet[$ch])) continue;
            $buffer = ($buffer << 5) | $alphabet[$ch]; $bits += 5;
            if ($bits >= 8) { $bits -= 8; $out .= chr(($buffer >> $bits) & 0xff); }
        }
        return $out;
    }
}
