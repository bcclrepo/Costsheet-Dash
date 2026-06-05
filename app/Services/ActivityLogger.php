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
     * @param  string       $action      CREATE | UPDATE | DELETE | UPLOAD | LOGIN | LOGOUT
     * @param  string       $description Human-readable summary
     * @param  array        $context     Optional: model_type, model_id, changes, area_name, mine_code, year, quarter
     */
    public static function log(string $action, string $description, array $context = []): void
    {
        $user   = Auth::user();
        $now    = now();
        $month  = (int) $now->format('n');
        $fyYear = $month >= 4 ? (int) $now->format('Y') : (int) $now->format('Y') - 1;

        // Determine current financial quarter for file naming
        $quarter = match (true) {
            $month >= 4  && $month <= 6  => 'Q1',
            $month >= 7  && $month <= 9  => 'Q2',
            $month >= 10 && $month <= 12 => 'Q3',
            default                       => 'Q4',
        };

        $logFile = "activity_logs/FY{$fyYear}_{$quarter}.log";

        // Ensure directory exists
        if (!Storage::disk('local')->exists('activity_logs')) {
            Storage::disk('local')->makeDirectory('activity_logs');
        }

        // Build text log entry
        $changesStr = '';
        if (!empty($context['changes'])) {
            foreach ($context['changes'] as $field => $diff) {
                $old = is_array($diff) ? ($diff['old'] ?? '') : $diff;
                $new = is_array($diff) ? ($diff['new'] ?? '') : '';
                $changesStr .= "    FIELD:{$field} | OLD:[{$old}] => NEW:[{$new}]\n";
            }
        }

        $userName  = $user ? $user->name : 'System';
        $pisNumber = $user ? ($user->pis_number ?? 'N/A') : 'N/A';
        $ip        = request()?->ip() ?? 'CLI';

        $entry = implode("\n", array_filter([
            str_repeat('─', 80),
            "TIMESTAMP : {$now->format('Y-m-d H:i:s')}",
            "ACTION    : {$action}",
            "USER      : {$userName} | PIS: {$pisNumber}",
            "IP        : {$ip}",
            "DESC      : {$description}",
            !empty($context['area_name'])  ? "AREA      : {$context['area_name']}" : '',
            !empty($context['mine_code'])  ? "MINE      : {$context['mine_code']}" : '',
            !empty($context['year'])       ? "FY / QTR  : FY{$context['year']}-" . ($context['year'] + 1) . " / " . ($context['quarter'] ?? '-') : '',
            !empty($changesStr)            ? "CHANGES   :\n{$changesStr}" : '',
        ])) . "\n";

        Storage::disk('local')->append($logFile, $entry);

        // Persist to DB
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
            'log_file'   => $logFile,
        ]);
    }
}
