<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\CostsheetData;
use App\Models\Mine;
use App\Models\UploadedFile;
use Illuminate\Http\Request;

class PublicCostsheetController extends Controller
{
    public function index(Request $request)
    {
        $areas        = Area::where('is_active', true)->orderBy('name')->get();
        $selectedArea = $request->get('area_id');
        $selectedYear = $request->get('year', fy_start());
        $selectedMode = $request->get('mode', 'Q1');

        $mines         = collect();
        $costsheetRows = [];
        $yearlyData    = [];
        $csvTotals     = [];

        if ($selectedArea) {
            $mines = Mine::where('area_id', $selectedArea)
                ->where('is_active', true)->orderBy('mine_code')->get();

            if ($selectedMode === 'YEARLY') {
                foreach ($mines as $mine) {
                    $allData = CostsheetData::where('mine_id', $mine->id)
                        ->where('year', $selectedYear)->get()->keyBy('quarter');
                    $yearlyData[$mine->id] = $this->aggregate($allData);
                }
                $csvTotals = $this->yearlyCsvTotals($selectedArea, $selectedYear);
            } else {
                foreach ($mines as $mine) {
                    $costsheetRows[$mine->id] = CostsheetData::where('mine_id', $mine->id)
                        ->where('year', $selectedYear)->where('quarter', $selectedMode)->first();
                }
                $uf = UploadedFile::where('area_id', $selectedArea)
                    ->where('year', $selectedYear)->where('quarter', $selectedMode)
                    ->latest()->first();
                $csvTotals = $uf?->area_totals ?? [];
            }
        }

        $metrics = (new DataController)->getMetrics();

        return view('public.costsheet', compact(
            'areas', 'mines', 'costsheetRows', 'yearlyData',
            'metrics', 'selectedArea', 'selectedYear', 'selectedMode', 'csvTotals'
        ));
    }

    private function aggregate($allData): array
    {
        $sumFields = ['production_qty','dispatch_qty','obr_qty','net_sales','total_relevant_cost','costing_profit'];
        $result    = array_fill_keys(array_merge($sumFields, ['stripping_ratio','spt','cpt','profit_per_tonne']), null);
        foreach (['Q1','Q2','Q3','Q4'] as $q) {
            $d = $allData[$q] ?? null;
            if (!$d) continue;
            foreach ($sumFields as $f) {
                if ($d->$f !== null) $result[$f] = ($result[$f] ?? 0) + $d->$f;
            }
        }
        $result['stripping_ratio'] = ($result['production_qty'] && $result['obr_qty'])
            ? round($result['obr_qty'] / $result['production_qty'], 4) : null;
        $result['spt']             = ($result['dispatch_qty'] && $result['net_sales'])
            ? round($result['net_sales'] / $result['dispatch_qty'] * 100000, 2) : null;
        $result['cpt']             = ($result['dispatch_qty'] && $result['total_relevant_cost'])
            ? round($result['total_relevant_cost'] / $result['dispatch_qty'] * 100000, 2) : null;
        $result['profit_per_tonne'] = ($result['dispatch_qty'] && $result['costing_profit'] !== null)
            ? round($result['costing_profit'] / $result['dispatch_qty'] * 100000, 2) : null;
        return $result;
    }

    private function yearlyCsvTotals(int $areaId, int $year): array
    {
        $sumFields = ['production_qty','dispatch_qty','obr_qty','net_sales','total_relevant_cost','costing_profit'];
        $t = [];
        foreach (UploadedFile::where('area_id', $areaId)->where('year', $year)->whereNotNull('area_totals')->get() as $uf) {
            foreach ($uf->area_totals ?? [] as $field => $val) {
                if (in_array($field, $sumFields) && $val !== null) $t[$field] = ($t[$field] ?? 0) + $val;
            }
        }
        if (!empty($t)) {
            $t['stripping_ratio']  = ($t['production_qty'] ?? 0) > 0
                ? round(($t['obr_qty'] ?? 0) / $t['production_qty'], 4) : null;
            $t['spt']              = ($t['dispatch_qty'] ?? 0) > 0 ? round(($t['net_sales'] ?? 0) / $t['dispatch_qty'] * 100000, 2) : null;
            $t['cpt']              = ($t['dispatch_qty'] ?? 0) > 0 ? round(($t['total_relevant_cost'] ?? 0) / $t['dispatch_qty'] * 100000, 2) : null;
            $t['profit_per_tonne'] = ($t['dispatch_qty'] ?? 0) > 0 ? round(($t['costing_profit'] ?? 0) / $t['dispatch_qty'] * 100000, 2) : null;
        }
        return $t;
    }
}
