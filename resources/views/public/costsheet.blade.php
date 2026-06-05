<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCCL Costsheet – Public View</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .top-header {
            background: linear-gradient(90deg, #1a2035 0%, #1a3a7a 100%);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .top-header img { height: 52px; }
        .top-header .org-name { color: #fff; }
        .top-header .org-name h6 { margin: 0; font-weight: 700; font-size: 1rem; }
        .top-header .org-name small { color: rgba(255,255,255,.65); font-size: 11px; }
        .filter-card { border: none; box-shadow: 0 2px 8px rgba(0,0,0,.08); border-radius: 10px; }
        .table-costsheet th { background: #1a2035; color: #fff; font-size: 12px; white-space: nowrap; }
        .table-costsheet td { font-size: 13px; white-space: nowrap; }
        .table-costsheet td.metric-label { white-space: normal; min-width: 200px; }
        .metric-label { font-weight: 600; background: #f8f9fa; }
        .negative-val { color: #dc3545; font-weight: 500; }
        .btn-toggle { font-size: 12px; }
        .period-toggle .btn { border-radius: 4px !important; }
        @media (max-width: 576px) {
            .table-costsheet th, .table-costsheet td { font-size: 10px; }
            .period-toggle { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

<div class="top-header">
    @if(file_exists(public_path('images/logo.jpg')))
    <img src="{{ asset('images/logo.jpg') }}" alt="BCCL">
    @else
    <span style="color:#fff;font-size:1.6rem;font-weight:900;letter-spacing:2px">BCCL</span>
    @endif
    <div class="org-name">
        <h6>Bharat Coking Coal Limited</h6>
        <small>Costsheet Dashboard – Public View</small>
    </div>
</div>

<div class="container-fluid py-3">

    {{-- Filters --}}
    <div class="card filter-card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('public.costsheet') }}" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold mb-1 small">Area</label>
                    <select name="area_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Select Area --</option>
                        @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ $selectedArea == $area->id ? 'selected' : '' }}>
                            {{ $area->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold mb-1 small">Financial Year</label>
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach(fy_years() as $startYear => $label)
                        <option value="{{ $startYear }}" {{ $selectedYear == $startYear ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold mb-1 small">Period</label>
                    <div class="btn-group w-100 period-toggle" role="group">
                        @foreach(['Q1'=>'Q1','Q2'=>'Q2','Q3'=>'Q3','Q4'=>'Q4','YEARLY'=>'Full Year'] as $mode => $label)
                        <a href="{{ route('public.costsheet', ['area_id'=>$selectedArea,'year'=>$selectedYear,'mode'=>$mode]) }}"
                           class="btn btn-sm btn-toggle {{ $selectedMode === $mode ? ($mode==='YEARLY' ? 'btn-warning' : 'btn-primary') : 'btn-outline-secondary' }}">
                            {{ $label }}
                        </a>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($selectedArea && $mines->count() > 0)
    <div class="card" style="border:none;box-shadow:0 2px 8px rgba(0,0,0,.08);border-radius:10px">
        <div class="card-header bg-dark text-white py-2 d-flex justify-content-between flex-wrap gap-1">
            <span>
                <i class="bi bi-table me-2"></i>
                <strong>{{ $areas->firstWhere('id',$selectedArea)?->name }}</strong>
                — FY {{ fy_label((int)$selectedYear) }}
                — <span class="badge {{ $selectedMode==='YEARLY' ? 'bg-warning text-dark' : 'bg-primary' }}">
                    {{ $selectedMode==='YEARLY' ? 'Full Year' : $selectedMode }}
                </span>
            </span>
            <small class="opacity-75 align-self-center">All figures in Lakhs / Tonnes as noted</small>
        </div>
        <div class="card-body p-0 overflow-auto">
            <table class="table table-bordered table-sm table-costsheet mb-0">
                <thead>
                    <tr>
                        <th style="min-width:200px">DESCRIPTION</th>
                        <th class="text-center" style="min-width:55px">FML</th>
                        @foreach($mines as $mine)
                        <th class="text-center" style="min-width:110px">
                            {{ $mine->mine_code }}<br>
                            <span class="fw-normal" style="font-size:11px;white-space:normal;word-break:break-word;display:block">{{ $mine->mine_name }}</span>
                        </th>
                        @endforeach
                        <th class="text-center bg-secondary text-white" style="min-width:100px">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($metrics as $metric)
                    @php $isYearly = $selectedMode === 'YEARLY'; @endphp
                    <tr>
                        <td class="metric-label">{{ $metric['label'] }}</td>
                        <td class="text-center text-muted small fw-bold">{{ $metric['formula'] }}</td>
                        @foreach($mines as $mine)
                        @php
                            $val = $isYearly
                                ? ($yearlyData[$mine->id][$metric['key']] ?? null)
                                : ($costsheetRows[$mine->id]?->{$metric['key']} ?? null);
                        @endphp
                        <td class="text-end {{ $val !== null && $val < 0 ? 'negative-val' : '' }}">
                            {{ $val !== null ? number_format($val, 2) : '-' }}
                        </td>
                        @endforeach
                        @php $t = $csvTotals[$metric['key']] ?? null; @endphp
                        <td class="text-end fw-bold bg-light {{ $t !== null && $t < 0 ? 'negative-val' : '' }}">
                            {{ $t !== null ? number_format($t, 2) : '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted small py-1 px-3">
            Data displayed is for information purposes only. © {{ date('Y') }} BCCL
        </div>
    </div>
    @elseif($selectedArea)
    <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No data available for the selected period.</div>
    @else
    <div class="alert alert-secondary text-center py-4">
        <i class="bi bi-bar-chart-fill fs-1 d-block mb-2 text-primary"></i>
        Select an area and financial year to view costsheet data.
    </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
