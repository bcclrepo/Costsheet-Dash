<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\CostsheetData;
use App\Models\Mine;
use App\Models\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Validator;

class UploadController extends Controller
{
    public function index()
    {
        $user  = Auth::user();
        $areas = $user->accessibleAreas()->get();

        // area_admin/viewer only see their own uploads
        $historyQuery = UploadedFile::with(['area', 'uploader']);
        if (!$user->hasRole(['super_admin', 'admin'])) {
            $historyQuery->whereIn('area_id', $areas->pluck('id'));
        }
        $uploadHistory = $historyQuery->latest()->paginate(15);

        // Pre-select area when user has exactly one accessible area
        $autoArea = $areas->count() === 1 ? $areas->first() : null;

        return view('upload.index', compact('areas', 'uploadHistory', 'autoArea'));
    }

    public function preview(Request $request)
    {
        $request->validate([
            'area_id'  => 'required|exists:areas,id',
            'year'     => 'required|integer|min:2000|max:2099',
            'quarter'  => 'required|in:Q1,Q2,Q3,Q4',
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $area = Area::with('mines')->findOrFail($request->area_id);

        // Enforce area access
        if (!Auth::user()->canAccessArea($area->id)) {
            abort(403, 'You do not have permission to upload data for this area.');
        }

        $year    = (int) $request->year;
        $quarter = $request->quarter;

        $existingUpload = UploadedFile::where('area_id', $area->id)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->first();

        $file    = $request->file('csv_file');
        $origName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $file->getClientOriginalName());
        $csvData = $this->parseCsv($file->getPathname());

        if (empty($csvData['rows'])) {
            return redirect()->route('upload.index')
                ->withErrors(['csv_file' => 'No valid data rows found in CSV file. Check the file format.'])
                ->withInput();
        }

        $preview = $this->buildPreview($csvData, $area, $year, $quarter);

        // Save temp file with a safe name and store key in session
        $tempKey  = 'upload_temp_' . Auth::id();
        $tempName = 'tmp_' . time() . '_' . uniqid() . '.csv';

        Storage::disk('local')->put('csv_temp/' . $tempName, file_get_contents($file->getPathname()));

        // Store metadata in session — hidden form fields can be tampered with
        session([
            $tempKey => [
                'filename'      => $tempName,
                'orig_name'     => $origName,
                'area_id'       => $area->id,
                'year'          => $year,
                'quarter'       => $quarter,
                'has_existing'  => (bool) $existingUpload,
                'csv_totals'    => $preview['csv_totals'], // save CSV TOTAL column values
            ],
        ]);

        $csvTotals = $preview['csv_totals'];

        return view('upload.preview', compact(
            'area', 'year', 'quarter', 'preview', 'existingUpload', 'tempName', 'csvTotals'
        ));
    }

    public function store(Request $request)
    {
        $tempKey  = 'upload_temp_' . Auth::id();
        $tempMeta = session($tempKey);

        if (!$tempMeta) {
            return redirect()->route('upload.index')
                ->with('error', 'Session expired. Please upload the CSV again.');
        }

        $tempName    = $tempMeta['filename'];
        $origName    = $tempMeta['orig_name'];
        $areaId      = $tempMeta['area_id'];
        $year        = $tempMeta['year'];
        $quarter     = $tempMeta['quarter'];
        $hasExisting = $tempMeta['has_existing'];
        $csvTotals   = $tempMeta['csv_totals'] ?? [];

        // overwrite is determined by whether existing data was acknowledged
        $overwrite = $request->boolean('overwrite', false) || $hasExisting;

        if (!Storage::disk('local')->exists('csv_temp/' . $tempName)) {
            session()->forget($tempKey);
            return redirect()->route('upload.index')
                ->with('error', 'Temporary file not found. Please upload the CSV again.');
        }

        $area    = Area::with('mines')->findOrFail($areaId);
        $tmpPath = Storage::disk('local')->path('csv_temp/' . $tempName);
        $csvData = $this->parseCsv($tmpPath);
        $preview = $this->buildPreview($csvData, $area, $year, $quarter);

        DB::beginTransaction();
        try {
            $rowsImported = 0;
            $rowsSkipped  = 0;

            foreach ($preview['rows'] as $row) {
                if ($row['mine'] === null) {
                    $rowsSkipped++;
                    continue;
                }

                $mine = $row['mine'];
                $data = $row['data'];

                $existing = CostsheetData::where('mine_id', $mine->id)
                    ->where('year', $year)
                    ->where('quarter', $quarter)
                    ->first();

                if ($existing && !$overwrite) {
                    $rowsSkipped++;
                    continue;
                }

                CostsheetData::updateOrCreate(
                    ['mine_id' => $mine->id, 'year' => $year, 'quarter' => $quarter],
                    array_merge($data, [
                        'uploaded_by' => $existing ? $existing->uploaded_by : Auth::id(),
                        'updated_by'  => Auth::id(),
                    ])
                );
                $rowsImported++;
            }

            // Move to permanent area-wise storage
            $storedName = $year . '_' . $quarter . '_' . time() . '_' . $origName;
            $storedPath = 'csv_uploads/' . $areaId . '/' . $year . '/' . $quarter . '/' . $storedName;
            Storage::disk('local')->move('csv_temp/' . $tempName, $storedPath);

            UploadedFile::create([
                'area_id'           => $areaId,
                'year'              => $year,
                'quarter'           => $quarter,
                'original_filename' => $origName,
                'stored_filename'   => $storedName,
                'file_path'         => $storedPath,
                'rows_imported'     => $rowsImported,
                'rows_skipped'      => $rowsSkipped,
                'status'            => 'completed',
                'area_totals'       => $csvTotals, // TOTAL column values from the CSV
                'uploaded_by'       => Auth::id(),
            ]);

            DB::commit();
            session()->forget($tempKey);

            ActivityLogger::log('UPLOAD',
                "CSV uploaded for area {$area->name} — FY{$year} / {$quarter}: {$rowsImported} mines imported, {$rowsSkipped} skipped.",
                ['area_name' => $area->name, 'year' => $year, 'quarter' => $quarter,
                 'model_type' => 'UploadedFile']
            );

            return redirect()->route('upload.index')
                ->with('success', "Upload successful! {$rowsImported} mine(s) imported, {$rowsSkipped} skipped.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('upload.index')
                ->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    // ─── Bulk Upload (all areas in one CSV) ─────────────────────────────────────

    public function bulkIndex()
    {
        $uploadHistory = UploadedFile::with(['area', 'uploader'])
            ->where('original_filename', 'like', 'BULK_%')
            ->latest()->paginate(15);
        return view('upload.bulk-index', compact('uploadHistory'));
    }

    public function bulkPreview(Request $request)
    {
        $request->validate([
            'year'     => 'required|integer|min:2000|max:2099',
            'quarter'  => 'required|in:Q1,Q2,Q3,Q4',
            'csv_file' => 'required|file|mimes:csv,txt|max:20480',
        ]);

        $year    = (int) $request->year;
        $quarter = $request->quarter;
        $file    = $request->file('csv_file');
        $origName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $file->getClientOriginalName());

        $csvData = $this->parseCsv($file->getPathname());
        if (empty($csvData['rows'])) {
            return redirect()->route('upload.bulk.index')
                ->withErrors(['csv_file' => 'No valid data rows found in CSV.'])->withInput();
        }

        $result  = $this->buildBulkPreview($csvData, $year, $quarter);

        // Save temp
        $tempKey  = 'bulk_temp_' . Auth::id();
        $tempName = 'bulk_tmp_' . time() . '_' . uniqid() . '.csv';
        Storage::disk('local')->put('csv_temp/' . $tempName, file_get_contents($file->getPathname()));

        session([$tempKey => [
            'filename'  => $tempName,
            'orig_name' => $origName,
            'year'      => $year,
            'quarter'   => $quarter,
        ]]);

        return view('upload.bulk-preview', compact('result', 'year', 'quarter', 'origName'));
    }

    public function bulkStore(Request $request)
    {
        $tempKey  = 'bulk_temp_' . Auth::id();
        $tempMeta = session($tempKey);

        if (!$tempMeta) {
            return redirect()->route('upload.bulk.index')
                ->with('error', 'Session expired. Please upload the CSV again.');
        }

        $tempName = $tempMeta['filename'];
        $origName = $tempMeta['orig_name'];
        $year     = $tempMeta['year'];
        $quarter  = $tempMeta['quarter'];

        if (!Storage::disk('local')->exists('csv_temp/' . $tempName)) {
            session()->forget($tempKey);
            return redirect()->route('upload.bulk.index')
                ->with('error', 'Temporary file not found. Please upload again.');
        }

        $csvData = $this->parseCsv(Storage::disk('local')->path('csv_temp/' . $tempName));
        $result  = $this->buildBulkPreview($csvData, $year, $quarter);
        $overwrite = $request->boolean('overwrite', true);

        DB::beginTransaction();
        try {
            $totalImported = 0;
            $totalSkipped  = 0;
            $areaGroups    = []; // area_id => ['rows' => [...], 'totals' => [...]]

            foreach ($result['mine_rows'] as $row) {
                if (!$row['mine'] || !$row['data']) {
                    $totalSkipped++;
                    continue;
                }
                $mine  = $row['mine'];
                $areaId = $mine->area_id;

                $existing = CostsheetData::where('mine_id', $mine->id)
                    ->where('year', $year)->where('quarter', $quarter)->first();

                CostsheetData::updateOrCreate(
                    ['mine_id' => $mine->id, 'year' => $year, 'quarter' => $quarter],
                    array_merge($row['data'], [
                        'uploaded_by' => $existing ? $existing->uploaded_by : Auth::id(),
                        'updated_by'  => Auth::id(),
                    ])
                );
                $totalImported++;
                $areaGroups[$areaId]['count'] = ($areaGroups[$areaId]['count'] ?? 0) + 1;
            }

            // Save per-area totals and upload file records
            $storedName = $year . '_' . $quarter . '_' . time() . '_BULK_' . $origName;
            $storedPath = 'csv_uploads/bulk/' . $year . '/' . $quarter . '/' . $storedName;
            Storage::disk('local')->move('csv_temp/' . $tempName, $storedPath);

            foreach ($result['area_totals'] as $areaId => $totals) {
                $cnt = $areaGroups[$areaId]['count'] ?? 0;
                if ($cnt === 0) continue;
                UploadedFile::create([
                    'area_id'           => $areaId,
                    'year'              => $year,
                    'quarter'           => $quarter,
                    'original_filename' => 'BULK_' . $origName,
                    'stored_filename'   => $storedName,
                    'file_path'         => $storedPath,
                    'rows_imported'     => $cnt,
                    'rows_skipped'      => 0,
                    'status'            => 'completed',
                    'area_totals'       => $totals,
                    'uploaded_by'       => Auth::id(),
                ]);
            }

            DB::commit();
            session()->forget($tempKey);

            ActivityLogger::log('UPLOAD',
                "Bulk CSV uploaded — FY{$year} / {$quarter}: {$totalImported} mines across all areas imported.",
                ['year' => $year, 'quarter' => $quarter, 'model_type' => 'UploadedFile']
            );

            return redirect()->route('upload.bulk.index')
                ->with('success', "Bulk upload done! {$totalImported} mines imported, {$totalSkipped} skipped.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('upload.bulk.index')
                ->with('error', 'Bulk upload failed: ' . $e->getMessage());
        }
    }

    public function bulkTemplate()
    {
        $mines = Mine::with('area')->where('is_active', true)
            ->orderBy('area_id')->orderBy('mine_code')->get();

        $headers = ['DESCRIPTION', 'FORMULA FOR CALCULATION'];
        foreach ($mines as $mine) {
            $headers[] = $mine->mine_code . ' - ' . $mine->mine_name;
        }
        $headers[] = 'TOTAL';

        $metricLabels = [
            'PRODUCTION QTY (TE)', 'DISPATCH QTY (TE) (Excluding ST)', 'OBR QTY (M3)',
            'STRIPPING RATIO', 'NET SALES (LAKHS)', 'SPT (RS.PER TONNE)',
            'TOTAL RELEVENT COST FOR DISPATCHED COAL (LAKHS)',
            'CPT (RS. PER TONNE)', 'COSTING PROFIT (LAKHS)', 'PROFIT (RS. PER TONNE)',
        ];
        $formulas = ['A','B','C','D = C/A','E','F = E/B','G','H = G/B','I = E-G','J = I/B'];

        $csvContent = implode(',', array_map(fn($h) => '"' . str_replace('"','""',$h) . '"', $headers)) . "\n";
        foreach ($metricLabels as $i => $label) {
            $row = ['"' . $label . '"', '"' . $formulas[$i] . '"'];
            for ($j = 0; $j < count($mines) + 1; $j++) $row[] = '';
            $csvContent .= implode(',', $row) . "\n";
        }

        return response($csvContent, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bulk_costsheet_template.csv"',
        ]);
    }

    private function buildBulkPreview(array $csvData, int $year, string $quarter): array
    {
        $headers     = $csvData['headers'];
        $rows        = $csvData['rows'];
        $lastColIdx  = count($headers) - 1;
        $warnings    = [];
        $mineRows    = [];
        $areaTotals  = []; // area_id => metric totals from last CSV col
        $areaPreview = []; // area_id => ['area'=>Area, 'mines'=>[...]]

        $metricMap = [
            'PRODUCTION QTY'         => 'production_qty',
            'DISPATCH QTY'           => 'dispatch_qty',
            'OBR QTY'                => 'obr_qty',
            'STRIPPING RATIO'        => 'stripping_ratio',
            'NET SALES'              => 'net_sales',
            'SPT'                    => 'spt',
            'TOTAL RELEVENT COST'    => 'total_relevant_cost',
            'TOTAL RELEVANT COST'    => 'total_relevant_cost',
            'CPT'                    => 'cpt',
            'COSTING PROFIT'         => 'costing_profit',
            'PROFIT (RS. PER TONNE)' => 'profit_per_tonne',
            'PROFIT (RS.PER TONNE)'  => 'profit_per_tonne',
        ];

        // Build mine columns from header (skip col 0, 1, last)
        $mineColumns = [];
        for ($i = 2; $i < $lastColIdx; $i++) {
            $parts    = explode(' - ', $headers[$i], 2);
            $mineCode = trim($parts[0]);
            $mine     = Mine::with('area')->where('mine_code', $mineCode)->first();
            if (!$mine) {
                $warnings[] = "Mine code '{$mineCode}' not found in master — skipped.";
            }
            $mineColumns[$i] = ['code' => $mineCode, 'mine' => $mine];
        }

        // Collect per-mine data + TOTAL column per row
        $mineData     = array_fill_keys(array_keys($mineColumns), []);
        $totalColData = [];

        foreach ($rows as $row) {
            $description = strtoupper(trim($row[0] ?? ''));
            $fieldKey    = null;
            foreach ($metricMap as $keyword => $key) {
                if (str_contains($description, $keyword)) { $fieldKey = $key; break; }
            }
            if (!$fieldKey) continue;

            foreach ($mineColumns as $colIdx => $colInfo) {
                $mineData[$colIdx][$fieldKey] = $this->parseNumber($row[$colIdx] ?? null);
            }
            $totalColData[$fieldKey] = $this->parseNumber($row[$lastColIdx] ?? null);
        }

        // Group by area for preview; total column belongs to "TOTAL" per area needs context.
        // Since the bulk CSV has no per-area subtotals, we use the single last column as grand total.
        // Per-area totals: sum mine values within each area (no per-area total col in bulk CSV).
        $areaMineCols = []; // area_id => [colIdx => colInfo]
        foreach ($mineColumns as $colIdx => $colInfo) {
            $mine = $colInfo['mine'];
            if (!$mine) continue;
            $areaMineCols[$mine->area_id][$colIdx] = $colInfo;
        }

        // Build per-mine rows and per-area preview structure
        foreach ($mineColumns as $colIdx => $colInfo) {
            $mine = $colInfo['mine'];
            $data = $mineData[$colIdx] ?? [];
            $hasData = !empty(array_filter($data, fn($v) => $v !== null));

            $existing = $mine ? CostsheetData::where('mine_id', $mine->id)
                ->where('year', $year)->where('quarter', $quarter)->first() : null;

            $mineRows[] = [
                'col_idx'     => $colIdx,
                'mine_code'   => $colInfo['code'],
                'mine'        => $mine,
                'area'        => $mine?->area,
                'data'        => $hasData ? $data : [],
                'existing'    => $existing,
                'has_warning' => !$mine,
                'no_data'     => !$hasData,
            ];

            if (!$mine) continue;
            $areaId = $mine->area_id;
            if (!isset($areaPreview[$areaId])) {
                $areaPreview[$areaId] = ['area' => $mine->area, 'mine_rows' => []];
            }
            $areaPreview[$areaId]['mine_rows'][] = end($mineRows);
        }

        // Area totals: sum raw mine values within each area from CSV
        foreach ($areaMineCols as $areaId => $cols) {
            $sumFields = ['production_qty','dispatch_qty','obr_qty','net_sales','total_relevant_cost','costing_profit'];
            $t = [];
            foreach ($cols as $colIdx => $colInfo) {
                $d = $mineData[$colIdx] ?? [];
                foreach ($sumFields as $f) {
                    if (isset($d[$f]) && $d[$f] !== null) $t[$f] = ($t[$f] ?? 0) + $d[$f];
                }
            }
            if (!empty($t)) {
                $t['stripping_ratio']  = ($t['production_qty'] ?? 0) > 0 ? round(($t['obr_qty'] ?? 0) / $t['production_qty'], 4) : null;
                $t['spt']              = ($t['dispatch_qty'] ?? 0) > 0 ? round(($t['net_sales'] ?? 0) / $t['dispatch_qty'] * 100000, 2) : null;
                $t['cpt']              = ($t['dispatch_qty'] ?? 0) > 0 ? round(($t['total_relevant_cost'] ?? 0) / $t['dispatch_qty'] * 100000, 2) : null;
                $t['profit_per_tonne'] = ($t['dispatch_qty'] ?? 0) > 0 ? round(($t['costing_profit'] ?? 0) / $t['dispatch_qty'] * 100000, 2) : null;
            }
            $areaTotals[$areaId] = $t;
        }

        return [
            'mine_rows'    => $mineRows,
            'area_preview' => $areaPreview,
            'area_totals'  => $areaTotals,
            'grand_total'  => $totalColData, // last CSV col = grand total
            'warnings'     => $warnings,
        ];
    }

    // ─── Area-wise template download ───────────────────────────────────────────

    public function downloadTemplate()
    {
        $mines = Mine::with('area')->where('is_active', true)->orderBy('mine_code')->get();

        $headers = ['DESCRIPTION', 'FORMULA FOR CALCULATION'];
        foreach ($mines as $mine) {
            $headers[] = $mine->mine_code . ' - ' . $mine->mine_name;
        }
        $headers[] = 'TOTAL';

        $metrics = [
            ['PRODUCTION QTY (TE)',                              'A'],
            ['DISPATCH QTY (TE) (Excluding ST)',                 'B'],
            ['OBR QTY (M3)',                                     'C'],
            ['STRIPPING RATIO',                                  'D = C/A'],
            ['NET SALES (LAKHS)',                                'E'],
            ['SPT (RS.PER TONNE)',                               'F = E/B'],
            ['TOTAL RELEVENT COST FOR DISPATCHED COAL (LAKHS)', 'G'],
            ['CPT (RS. PER TONNE)',                             'H = G/B'],
            ['COSTING PROFIT (LAKHS)',                          'I = E-G'],
            ['PROFIT (RS. PER TONNE)',                          'J = I/B'],
        ];

        $csvContent = implode(',', array_map(fn($h) => '"' . str_replace('"', '""', $h) . '"', $headers)) . "\n";
        foreach ($metrics as $metric) {
            $row = ['"' . $metric[0] . '"', '"' . $metric[1] . '"'];
            for ($i = 0; $i < count($mines) + 1; $i++) {
                $row[] = '';
            }
            $csvContent .= implode(',', $row) . "\n";
        }

        return response($csvContent, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="costsheet_template.csv"',
        ]);
    }

    private function parseCsv(string $filePath): array
    {
        $rows    = [];
        $headers = [];

        $content = file_get_contents($filePath);
        // Strip UTF-8 BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        // Normalise line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        $tmpFile = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($tmpFile, $content);

        if (($handle = fopen($tmpFile, 'r')) !== false) {
            $lineNum = 0;
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $lineNum++;
                if ($lineNum === 1) {
                    $headers = array_map('trim', $data);
                    continue;
                }
                $trimmed = array_map('trim', $data);
                if (!empty(array_filter($trimmed, fn($v) => $v !== ''))) {
                    $rows[] = $trimmed;
                }
            }
            fclose($handle);
        }
        @unlink($tmpFile);

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function buildPreview(array $csvData, Area $area, int $year, string $quarter): array
    {
        $headers     = $csvData['headers'];
        $rows        = $csvData['rows'];
        $warnings    = [];
        $previewRows = [];

        $lastColIdx = count($headers) - 1; // TOTAL column index

        // Mine columns: col 0=DESCRIPTION, col 1=FORMULA, cols 2..(last-1)=mines, last=TOTAL
        $mineColumns = [];
        for ($i = 2; $i < $lastColIdx; $i++) {
            $parts    = explode(' - ', $headers[$i], 2);
            $mineCode = trim($parts[0]);

            $mine = Mine::where('mine_code', $mineCode)->where('area_id', $area->id)->first()
                 ?? Mine::where('mine_code', $mineCode)->first();

            if (!$mine) {
                $warnings[] = "Mine code '{$mineCode}' not found in database — column will be skipped.";
            }
            $mineColumns[$i] = ['code' => $mineCode, 'mine' => $mine];
        }

        $metricMap = [
            'PRODUCTION QTY'         => 'production_qty',
            'DISPATCH QTY'           => 'dispatch_qty',
            'OBR QTY'                => 'obr_qty',
            'STRIPPING RATIO'        => 'stripping_ratio',
            'NET SALES'              => 'net_sales',
            'SPT'                    => 'spt',
            'TOTAL RELEVENT COST'    => 'total_relevant_cost',
            'TOTAL RELEVANT COST'    => 'total_relevant_cost',
            'CPT'                    => 'cpt',
            'COSTING PROFIT'         => 'costing_profit',
            'PROFIT (RS. PER TONNE)' => 'profit_per_tonne',
            'PROFIT (RS.PER TONNE)'  => 'profit_per_tonne',
        ];

        // Collect per-mine data AND the CSV TOTAL column
        $mineData  = array_fill_keys(array_keys($mineColumns), []);
        $csvTotals = []; // keyed by field name, values from last CSV column

        foreach ($rows as $row) {
            $description = strtoupper(trim($row[0] ?? ''));
            $fieldKey    = null;
            foreach ($metricMap as $keyword => $key) {
                if (str_contains($description, $keyword)) {
                    $fieldKey = $key;
                    break;
                }
            }
            if (!$fieldKey) continue;

            // Per-mine values
            foreach ($mineColumns as $colIdx => $colInfo) {
                $mineData[$colIdx][$fieldKey] = $this->parseNumber($row[$colIdx] ?? null);
            }

            // TOTAL column — read directly from last column of this row
            $csvTotals[$fieldKey] = $this->parseNumber($row[$lastColIdx] ?? null);
        }

        foreach ($mineColumns as $colIdx => $colInfo) {
            $mine     = $colInfo['mine'];
            $existing = $mine
                ? CostsheetData::where('mine_id', $mine->id)
                    ->where('year', $year)->where('quarter', $quarter)->first()
                : null;

            $previewRows[] = [
                'mine_code'   => $colInfo['code'],
                'mine'        => $mine,
                'data'        => $mineData[$colIdx] ?? [],
                'existing'    => $existing,
                'has_warning' => $mine === null,
            ];
        }

        return [
            'headers'      => $headers,
            'rows'         => $previewRows,
            'warnings'     => $warnings,
            'mine_columns' => $mineColumns,
            'csv_totals'   => $csvTotals, // from CSV TOTAL column, not calculated
        ];
    }

    private function parseNumber(?string $value): ?float
    {
        if ($value === null || trim($value) === '' || trim($value) === '-') {
            return null;
        }
        $clean = str_replace([',', ' '], '', trim($value));
        return is_numeric($clean) ? (float) $clean : null;
    }
}
