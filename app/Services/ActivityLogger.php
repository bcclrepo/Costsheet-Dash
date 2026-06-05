<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ActivityLogger
{
    /**
     * Log any event.
     *
     * @param  string  $action      CREATE | UPDATE | DELETE | UPLOAD | LOGIN | LOGOUT | LOGIN_FAILED
     * @param  string  $description Human-readable summary
     * @param  array   $context     Optional: model_type, model_id, changes, area_name,
     *                              mine_code, year, quarter, actor (User for explicit override)
     */
    public static function log(string $action, string $description, array $context = []): void
    {
        // Actor: explicit override (e.g. failed login where Auth::user() is null), else current user
        $user = $context['actor'] ?? Auth::user();

        $now    = now();
        $month  = (int) $now->format('n');
        $fyYear = $month >= 4 ? (int) $now->format('Y') : (int) $now->format('Y') - 1;

        $quarter = match (true) {
            $month >= 4  && $month <= 6  => 'Q1',
            $month >= 7  && $month <= 9  => 'Q2',
            $month >= 10 && $month <= 12 => 'Q3',
            default                       => 'Q4',
        };

        $logFile = "activity_logs/FY{$fyYear}_{$quarter}.log";

        if (!Storage::disk('local')->exists('activity_logs')) {
            Storage::disk('local')->makeDirectory('activity_logs');
        }

        // ── Request / client details ────────────────────────────────────────────
        $request   = request();
        $ip        = $request?->ip() ?? 'CLI';
        $userAgent = $request?->userAgent() ?? 'CLI';
        $url       = $request ? $request->fullUrl() : 'CLI';
        $method    = $request?->method() ?? '-';
        $agent     = self::parseUserAgent($userAgent);

        $userName  = $user ? $user->name : 'Guest';
        $pisNumber = $user ? ($user->pis_number ?? 'N/A') : 'N/A';

        // ── Field changes block (for UPDATE) ────────────────────────────────────
        $changesStr = '';
        if (!empty($context['changes'])) {
            foreach ($context['changes'] as $field => $diff) {
                $old = is_array($diff) ? ($diff['old'] ?? '') : $diff;
                $new = is_array($diff) ? ($diff['new'] ?? '') : '';
                $changesStr .= "    FIELD: {$field} | OLD: [{$old}] => NEW: [{$new}]\n";
            }
        }

        // ── Quarterly text-file entry ───────────────────────────────────────────
        $entry = implode("\n", array_filter([
            str_repeat('─', 90),
            "TIMESTAMP : {$now->format('Y-m-d H:i:s')} (" . $now->format('l') . ")",
            "ACTION    : {$action}",
            "USER      : {$userName} | PIS: {$pisNumber}" . ($user ? " | ID: {$user->id}" : ''),
            "IP        : {$ip}",
            "CLIENT    : {$agent['browser']} on {$agent['platform']} ({$agent['device']})",
            "URL       : {$method} {$url}",
            "DESC      : {$description}",
            !empty($context['area_name']) ? "AREA      : {$context['area_name']}" : '',
            !empty($context['mine_code']) ? "MINE      : {$context['mine_code']}" : '',
            !empty($context['year'])      ? "FY / QTR  : FY{$context['year']}-" . ($context['year'] + 1) . " / " . ($context['quarter'] ?? '-') : '',
            !empty($changesStr)           ? "CHANGES   :\n{$changesStr}" : '',
            "USER-AGENT: {$userAgent}",
        ], fn ($v) => $v !== '' && $v !== null)) . "\n";

        Storage::disk('local')->append($logFile, $entry);

        // ── DB record ───────────────────────────────────────────────────────────
        ActivityLog::create([
            'user_id'    => $user?->id,
            'user_name'  => $userName,
            'pis_number' => $pisNumber,
            'action'     => $action,
            'model_type' => $context['model_type'] ?? null,
            'model_id'   => $context['model_id']   ?? null,
            'description'=> $description,
            'changes'    => $context['changes']     ?? null,
            'area_name'  => $context['area_name']   ?? null,
            'mine_code'  => $context['mine_code']   ?? null,
            'year'       => $context['year']        ?? null,
            'quarter'    => $context['quarter']     ?? null,
            'ip_address' => $ip,
            'browser'    => $agent['browser'],
            'platform'   => $agent['platform'],
            'device'     => $agent['device'],
            'user_agent' => $userAgent,
            'url'        => mb_substr($url, 0, 255),
            'method'     => $method,
            'log_file'   => $logFile,
        ]);
    }

    /**
     * Lightweight user-agent parser → browser, platform, device type.
     */
    private static function parseUserAgent(string $ua): array
    {
        $browser  = 'Unknown';
        $platform = 'Unknown';
        $device   = 'Desktop';

        if ($ua === '' || $ua === 'CLI') {
            return ['browser' => 'CLI', 'platform' => 'Server', 'device' => 'Console'];
        }

        // Platform / OS
        $platforms = [
            'Windows NT 10.0' => 'Windows 10/11',
            'Windows NT'      => 'Windows',
            'Android'         => 'Android',
            'iPhone'          => 'iOS (iPhone)',
            'iPad'            => 'iPadOS',
            'Macintosh'       => 'macOS',
            'Mac OS X'        => 'macOS',
            'Linux'           => 'Linux',
            'CrOS'            => 'ChromeOS',
        ];
        foreach ($platforms as $needle => $label) {
            if (stripos($ua, $needle) !== false) { $platform = $label; break; }
        }

        // Browser (order matters — Edge/Brave before Chrome, Chrome before Safari)
        $browsers = [
            'Edg'      => 'Microsoft Edge',
            'OPR'      => 'Opera',
            'Brave'    => 'Brave',
            'Chrome'   => 'Chrome',
            'Firefox'  => 'Firefox',
            'Safari'   => 'Safari',
            'MSIE'     => 'Internet Explorer',
            'Trident'  => 'Internet Explorer',
        ];
        foreach ($browsers as $needle => $label) {
            if (stripos($ua, $needle) !== false) { $browser = $label; break; }
        }

        // Device type
        if (stripos($ua, 'Mobile') !== false || stripos($ua, 'iPhone') !== false || stripos($ua, 'Android') !== false) {
            $device = (stripos($ua, 'iPad') !== false || stripos($ua, 'Tablet') !== false) ? 'Tablet' : 'Mobile';
        } elseif (stripos($ua, 'iPad') !== false || stripos($ua, 'Tablet') !== false) {
            $device = 'Tablet';
        }

        return ['browser' => $browser, 'platform' => $platform, 'device' => $device];
    }
}
