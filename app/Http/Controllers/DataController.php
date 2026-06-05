<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\CostsheetData;
use App\Models\Mine;
use App\Models\UploadedFile;
use Illuminate\Http\Request;

class DataController extends Controller
{
    // Single combined view: quarterly (Q1-Q4) + yearly, with toggle.
    // area_id='ALL' shows all-areas view (admin/super_admin only).
    public function view(Request $request)
    {
        $user  = auth()->user();
        $areas = $user->accessibleAreas()->get();

        $selectedYear = $request->get('year', fy_start());
        $selectedMode = $request->get('mode', 'Q1');

        // Auto-select area when user has only one
        if (!$request->has('area_id') && $areas->count() === 1) {
            return redirect()->route('data.view', array_merge($request->query(), [
                'area_id' => $areas->first()->id,
                'year'    => $selectedYear,
                'mode'    => $selectedMode,
            ]));
        }

        $selectedArea = $request->get('area_id'); // may be a numeric id or 'ALL'
        $showAllAreas = ($selectedArea === 'ALL');

        // Only admin/super_admin may use the ALL option
        if ($showAllAreas && !$user->hasRole(['super_admin', 'admin'])) {
            abort(403);
        }

        if (!$showAllAreas && $selectedArea && !$user->canAccessArea((int) $selectedArea)) {
            abort(403, 'You do not have access to this area.');
        }

        // ── Single-area mode ───────────────────────────────────────────────────
        $mines         = collect();
        $costsheetRows = [];
        $yearlyData    = [];
        $csvTotals     = [];

        // ── All-areas mode ─────────────────────────────────────────────────────
        $allAreasData      = null; // null = not in all-areas mode
        $areaCsvTotals     = [];

        if ($showAllAreas) {
            $allAreasData = Area::where('is_active', true)->with(['mines' => function ($q) {
                $q->where('is_active', true)->orderBy('mine_code');
            }])->orderBy('name')->get();

            foreach ($allAreasData as $area) {
                if ($selectedMode === 'YEARLY') {
                    foreach ($area->mines as $mine) {
                        $rec = CostsheetData::where('mine_id', $mine->id)
                            ->where('year', $selectedYear)->get()->keyBy('quarter');
                        $yearlyData[$mine->id] = $this->aggregateYearlyData($rec);
                    }
                    $areaCsvTotals[$area->id] = $this->getYearlyCsvTotals($area->id, $selectedYear);
                } else {
                    foreach ($area->mines as $mine) {
                        $costsheetRows[$mine->id] = CostsheetData::where('mine_id', $mine->id)
                            ->where('year', $selectedYear)->where('quarter', $selectedMode)->first();
                    }
                    $uf = UploadedFile::where('area_id', $area->id)
                        ->where('year', $selectedYear)->where('quarter', $selectedMode)->latest()->first();
                    $areaCsvTotals[$area->id] = $uf?->area_totals ?? [];
                }
            }
        } elseif ($selectedArea) {
            $mines = Mine::where('area_id', $selectedArea)->where('is_active', true)
                ->orderBy('mine_code')->get();

            if ($selectedMode === 'YEARLY') {
                foreach ($mines as $mine) {
                    $allData = CostsheetData::where('mine_id', $mine->id)
                        ->where('year', $selectedYear)->get()->keyBy('quarter');
                    $yearlyData[$mine->id] = $this->aggregateYearlyData($allData);
                }
                $csvTotals = $this->getYearlyCsvTotals($selectedArea, $selectedYear);
            } else {
                foreach ($mines as $mine) {
                    $costsheetRows[$mine->id] = CostsheetData::where('mine_id', $mine->id)
                        ->where('year', $selectedYear)->where('quarter', $selectedMode)->first();
                }
                $uf = UploadedFile::where('area_id', $selectedArea)
                    ->where('year', $selectedYear)->where('quarter', $selectedMode)->latest()->first();
                $csvTotals = $uf?->area_totals ?? [];
            }
        }

        $metrics     = $this->getMetrics();
        $canViewAll  = $user->hasRole(['super_admin', 'admin']);

        return view('data.view', compact(
            'areas', 'mines', 'costsheetRows', 'yearlyData',
            'metrics', 'selectedArea', 'selectedYear', 'selectedMode',
            'csvTotals', 'showAllAreas', 'allAreasData', 'areaCsvTotals', 'canViewAll'
        ));
    }

    // Keep allAreas() route for backward compatibility but redirect to unified view
    public function allAreas(Request $request)
    {
        $selectedYear    = $request->get('year', fy_start());
        $selectedMode    = $request->get('mode', 'Q1');

        $areas = Area::where('is_active', true)->with(['mines' => function ($q) {
            $q->where('is_active', true)->orderBy('mine_code');
        }])->orderBy('name')->get();

        $data          = [];
        $areaCsvTotals = [];

        foreach ($areas as $area) {
            if ($selectedMode === 'YEARLY') {
                foreach ($area->mines as $mine) {
                    $records = CostsheetData::where('mine_id', $mine->id)
                        ->where('year', $selectedYear)->get()->keyBy('quarter');
                    $data[$mine->id] = $this->aggregateYearlyData($records);
                }
                $areaCsvTotals[$area->id] = $this->getYearlyCsvTotals($area->id, $selectedYear);
            } else {
                foreach ($area->mines as $mine) {
                    $data[$mine->id] = CostsheetData::where('mine_id', $mine->id)
                        ->where('year', $selectedYear)
                        ->where('quarter', $selectedMode)
                        ->first();
                }
                $uf = UploadedFile::where('area_id', $area->id)
                    ->where('year', $selectedYear)
                    ->where('quarter', $selectedMode)
                    ->latest()->first();
                $areaCsvTotals[$area->id] = $uf?->area_totals ?? [];
            }
        }

        $metrics = $this->getMetrics();

        return view('data.all-areas', compact(
            'areas', 'data', 'metrics',
            'selectedYear', 'selectedMode', 'areaCsvTotals'
        ));
    }

    public function edit(Request $request, int $mineId)
    {
        $mine    = Mine::with('area')->findOrFail($mineId);
        $year    = $request->get('year', fy_start());
        $quarter = $request->get('quarter', 'Q1');

        $record  = CostsheetData::firstOrNew(
            ['mine_id' => $mineId, 'year' => $year, 'quarter' => $quarter]
        );

        $metrics = $this->getMetrics();

        return view('data.edit', compact('mine', 'year', 'quarter', 'record', 'metrics'));
    }

    public function update(Request $request, int $mineId)
    {
        $mine = Mine::findOrFail($mineId);

        $request->validate([
            'year'                => 'required|integer|min:2000|max:2099',
            'quarter'             => 'required|in:Q1,Q2,Q3,Q4',
            'production_qty'      => 'nullable|numeric',
            'dispatch_qty'        => 'nullable|numeric',
            'obr_qty'             => 'nullable|numeric',
            'net_sales'           => 'nullable|numeric',
            'total_relevant_cost' => 'nullable|numeric',
        ]);

        $year    = $request->year;
        $quarter = $request->quarter;

        $data = [
            'production_qty'      => $request->production_qty,
            'dispatch_qty'        => $request->dispatch_qty,
            'obr_qty'             => $request->obr_qty,
            'net_sales'           => $request->net_sales,
            'total_relevant_cost' => $request->total_relevant_cost,
            'updated_by'          => auth()->id(),
        ];

        $data['stripping_ratio'] = ($data['production_qty'] && $data['obr_qty'])
            ? round($data['obr_qty'] / $data['production_qty'], 4) : null;
        $data['spt'] = ($data['dispatch_qty'] && $data['net_sales'])
            ? round($data['net_sales'] / $data['dispatch_qty'] * 100000, 2) : null;
        $data['cpt'] = ($data['dispatch_qty'] && $data['total_relevant_cost'])
            ? round($data['total_relevant_cost'] / $data['dispatch_qty'] * 100000, 2) : null;
        $data['costing_profit'] = ($data['net_sales'] !== null && $data['total_relevant_cost'] !== null)
            ? round($data['net_sales'] - $data['total_relevant_cost'], 2) : null;
        $data['profit_per_tonne'] = ($data['dispatch_qty'] && $data['costing_profit'] !== null)
            ? round($data['costing_profit'] / $data['dispatch_qty'] * 100000, 2) : null;

        $existing = CostsheetData::where('mine_id', $mine->id)
            ->where('year', $year)->where('quarter', $quarter)->first();

        CostsheetData::updateOrCreate(
            ['mine_id' => $mine->id, 'year' => $year, 'quarter' => $quarter],
            array_merge($data, ['uploaded_by' => $existing ? $existing->uploaded_by : auth()->id()])
        );

        return redirect()->route('data.view', [
            'area_id' => $mine->area_id, 'year' => $year, 'mode' => $quarter,
        ])->with('success', 'Data updated successfully.');
    }

    public function getMetrics(): array
    {
        return [
            ['key' => 'production_qty',     'label' => 'PRODUCTION QTY (TE)',               'formula' => 'A'],
            ['key' => 'dispatch_qty',        'label' => 'DISPATCH QTY (TE) (Excl. ST)',      'formula' => 'B'],
            ['key' => 'obr_qty',             'label' => 'OBR QTY (M3)',                      'formula' => 'C'],
            ['key' => 'stripping_ratio',     'label' => 'STRIPPING RATIO',                   'formula' => 'D = C/A'],
            ['key' => 'net_sales',           'label' => 'NET SALES (LAKHS)',                 'formula' => 'E'],
            ['key' => 'spt',                 'label' => 'SPT (RS. PER TONNE)',               'formula' => 'F = E/B'],
            ['key' => 'total_relevant_cost', 'label' => 'TOTAL RELEVANT COST (LAKHS)',       'formula' => 'G'],
            ['key' => 'cpt',                 'label' => 'CPT (RS. PER TONNE)',               'formula' => 'H = G/B'],
            ['key' => 'costing_profit',      'label' => 'COSTING PROFIT (LAKHS)',            'formula' => 'I = E-G'],
            ['key' => 'profit_per_tonne',    'label' => 'PROFIT (RS. PER TONNE)',            'formula' => 'J = I/B'],
        ];
    }

    private function getYearlyCsvTotals(int $areaId, int $year): array
    {
        $sumFields = ['production_qty','dispatch_qty','obr_qty','net_sales','total_relevant_cost','costing_profit'];
        $t = [];
        $ufs = UploadedFile::where('area_id', $areaId)->where('year', $year)
            ->whereNotNull('area_totals')->get();
        foreach ($ufs as $uf) {
            foreach ($uf->area_totals ?? [] as $field => $val) {
                if (in_array($field, $sumFields) && $val !== null) {
                    $t[$field] = ($t[$field] ?? 0) + $val;
                }
            }
        }
        if (!empty($t)) {
            $t['stripping_ratio']  = ($t['production_qty'] ?? 0) > 0
                ? round(($t['obr_qty'] ?? 0) / $t['production_qty'], 4) : null;
            $t['spt']              = ($t['dispatch_qty'] ?? 0) > 0
                ? round(($t['net_sales'] ?? 0) / $t['dispatch_qty'] * 100000, 2) : null;
            $t['cpt']              = ($t['dispatch_qty'] ?? 0) > 0
                ? round(($t['total_relevant_cost'] ?? 0) / $t['dispatch_qty'] * 100000, 2) : null;
            $t['profit_per_tonne'] = ($t['dispatch_qty'] ?? 0) > 0
                ? round(($t['costing_profit'] ?? 0) / $t['dispatch_qty'] * 100000, 2) : null;
        }
        return $t;
    }

    private function aggregateYearlyData($allData): array
    {
        $sumFields = ['production_qty','dispatch_qty','obr_qty','net_sales','total_relevant_cost','costing_profit'];
        $result    = array_fill_keys(
            array_merge($sumFields, ['stripping_ratio','spt','cpt','profit_per_tonne']),
            null
        );
        foreach (['Q1','Q2','Q3','Q4'] as $q) {
            $qData = $allData[$q] ?? null;
            if (!$qData) continue;
            foreach ($sumFields as $field) {
                if ($qData->$field !== null) {
                    $result[$field] = ($result[$field] ?? 0) + $qData->$field;
                }
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
}
