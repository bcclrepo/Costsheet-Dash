<?php

namespace App\Observers;

use App\Models\CostsheetData;
use App\Services\ActivityLogger;

class CostsheetDataObserver
{
    private array $watchedFields = [
        'production_qty', 'dispatch_qty', 'obr_qty', 'stripping_ratio',
        'net_sales', 'spt', 'total_relevant_cost', 'cpt',
        'costing_profit', 'profit_per_tonne',
    ];

    public function created(CostsheetData $record): void
    {
        ActivityLogger::log('CREATE',
            "Costsheet data created for mine {$record->mine?->mine_code} [{$record->mine?->mine_name}]",
            $this->context($record)
        );
    }

    public function updated(CostsheetData $record): void
    {
        $dirty = $record->getDirty();
        $changes = [];

        foreach ($this->watchedFields as $field) {
            if (array_key_exists($field, $dirty)) {
                $changes[$field] = [
                    'old' => $record->getOriginal($field),
                    'new' => $dirty[$field],
                ];
            }
        }

        if (empty($changes)) return;

        ActivityLogger::log('UPDATE',
            "Costsheet data updated for mine {$record->mine?->mine_code} [{$record->mine?->mine_name}]",
            array_merge($this->context($record), ['changes' => $changes])
        );
    }

    public function deleted(CostsheetData $record): void
    {
        ActivityLogger::log('DELETE',
            "Costsheet data deleted for mine {$record->mine?->mine_code} [{$record->mine?->mine_name}]",
            $this->context($record)
        );
    }

    private function context(CostsheetData $record): array
    {
        return [
            'model_type' => 'CostsheetData',
            'model_id'   => $record->id,
            'area_name'  => $record->mine?->area?->name,
            'mine_code'  => $record->mine?->mine_code,
            'year'       => $record->year,
            'quarter'    => $record->quarter,
        ];
    }
}
