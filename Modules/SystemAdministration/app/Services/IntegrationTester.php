<?php

namespace Modules\SystemAdministration\Services;

use Modules\SystemAdministration\Models\IntegrationTestResult;
use Modules\SystemAdministration\Models\SystemIntegration;

/**
 * Real provider connectivity verification — actual TCP/SMTP/HTTP probes
 * with timeouts. A filled form is NEVER proof of a working integration.
 */
class IntegrationTester
{
    public function test(SystemIntegration $i, ?int $by = null): IntegrationTestResult
    {
        $config = method_exists($i, 'configArray') ? $i->configArray() : (is_array($i->config) ? $i->config : []);
        $t = microtime(true);
        [$status, $code, $msg] = $this->probe($i, $config);
        return IntegrationTestResult::create([
            'integration_id' => $i->id, 'status' => $status,
            'duration_ms' => (int)((microtime(true)-$t)*1000),
            'error_code' => $code, 'message' => $msg, 'tested_by' => $by,
        ]);
    }

    /** @return array{0:string,1:?string,2:?string} */
    private function probe(SystemIntegration $i, array $config): array
    {
        $type = strtolower($i->type ?? $i->provider ?? '');
        $host = $config['host'] ?? $config['smtp_host'] ?? $config['endpoint'] ?? null;
        if (! $host) return ['config_invalid', 'NO_HOST', 'No host/endpoint configured.'];

        // SMTP/email — real EHLO probe
        if (str_contains($type, 'mail') || str_contains($type, 'smtp') || isset($config['smtp_host'])) {
            return $this->probeSmtp($host, (int)($config['port'] ?? 587));
        }
        // Generic HTTP endpoint probe
        $scheme = str_starts_with($host, 'http') ? '' : 'https://';
        $ctx = stream_context_create(['http' => ['timeout' => 8, 'method' => 'HEAD']]);
        $r = @file_get_contents($scheme.$host, false, $ctx);
        if ($r === false) {
            $err = error_get_last()['message'] ?? 'unreachable';
            return str_contains($err, 'timed out') ? ['timeout', 'TIMEOUT', 'Connection timed out.']
                : ['unavailable', 'UNREACHABLE', 'Provider endpoint unreachable.'];
        }
        return ['successful', null, 'Endpoint reachable (HTTP).'];
    }

    private function probeSmtp(string $host, int $port): array
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, 8);
        if (! $fp) {
            return str_contains($errstr, 'timed out') ? ['timeout', 'TIMEOUT', $errstr]
                : ['unavailable', 'CONNECT_FAIL', "$errstr ($errno)"];
        }
        $banner = fgets($fp, 512);
        fwrite($fp, "EHLO xerin-erp\r\n");
        $resp = fgets($fp, 512);
        fwrite($fp, "QUIT\r\n"); fclose($fp);
        if (str_starts_with(trim($banner), '220') && str_starts_with(trim($resp), '250')) {
            return ['successful', null, 'SMTP handshake verified (EHLO 250).'];
        }
        return ['auth_failed', 'SMTP_BAD_RESPONSE', 'Unexpected SMTP response.'];
    }
}
