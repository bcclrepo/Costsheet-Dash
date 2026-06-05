@extends('layouts.app')
@section('title', $showAllAreas ? 'All Areas Costsheet' : 'Costsheet View')
@section('page-title', $showAllAreas ? 'All Areas Costsheet' : 'Costsheet View')

@section('content')

{{-- Filter bar --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('data.view') }}" id="filterForm" class="row g-2 align-items-end">

            {{-- Area selector --}}
            @if($areas->count() === 1 && !$canViewAll)
            <input type="hidden" name="area_id" value="{{ $areas->first()->id }}">
            <div class="col-md-4">
                <label class="form-label fw-semibold mb-1">Area</label>
                <div class="form-control form-control-sm bg-light fw-semibold">
                    <i class="bi bi-map me-1 text-primary"></i>{{ $areas->first()->name }}
                </div>
            </div>
            @else
            <div class="col-md-4">
                <label class="form-label fw-semibold mb-1">Area</label>
                <select name="area_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Select Area --</option>
                    @if($canViewAll)
                    <option value="ALL" {{ $selectedArea === 'ALL' ? 'selected' : '' }}>
                        ★ All Areas
                    </option>
                    <option disabled>──────────────</option>
                    @endif
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ $selectedArea == $area->id ? 'selected' : '' }}>
                            {{ $area->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Year --}}
            <div class="col-md-3">
                <label class="form-label fw-semibold mb-1">Financial Year</label>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach(fy_years() as $startYear => $label)
                        <option value="{{ $startYear }}" {{ $selectedYear == $startYear ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Period toggle --}}
            <div class="col-md-5">
                <label class="form-label fw-semibold mb-1">Period</label>
                <div class="btn-group w-100" role="group">
                    @foreach(['Q1'=>'Q1 (Apr-Jun)','Q2'=>'Q2 (Jul-Sep)','Q3'=>'Q3 (Oct-Dec)','Q4'=>'Q4 (Jan-Mar)','YEARLY'=>'Full Year'] as $mode => $label)
                    <a href="{{ route('data.view', ['area_id'=>$selectedArea,'year'=>$selectedYear,'mode'=>$mode]) }}"
                       class="btn btn-sm {{ $selectedMode === $mode ? ($mode==='YEARLY' ? 'btn-warning' : 'btn-primary') : 'btn-outline-secondary' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>
            </div>

        </form>
    </div>
</div>

@if($showAllAreas && $allAreasData)

{{-- ── ALL AREAS MODE ─────────────────────────────────────────────────────── --}}
@foreach($allAreasData as $area)
@if($area->mines->count() > 0)
<div class="card mb-3">
    <div class="card-header bg-dark text-white py-2">
        <i class="bi bi-map me-2"></i><strong>{{ $area->name }}</strong>
        <span class="badge bg-light text-dark ms-2">{{ $area->mines->count() }} mines</span>
        <span class="badge {{ $selectedMode==='YEARLY' ? 'bg-warning text-dark' : 'bg-primary' }} ms-1">
            FY {{ fy_label((int)$selectedYear) }} / {{ $selectedMode==='YEARLY' ? 'Full Year' : $selectedMode }}
        </span>
    </div>
    <div class="card-body p-0 overflow-auto">
        <table class="table table-bordered table-sm table-costsheet mb-0">
            <thead>
                <tr>
                    <th style="min-width:200px">DESCRIPTION</th>
                    <th class="text-center" style="min-width:55px">FML</th>
                    @foreach($area->mines as $mine)
                    <th class="text-center" style="min-width:120px">
                        {{ $mine->mine_code }}<br>
                        <span class="mine-name fw-normal">{{ $mine->mine_name }}</span>
                    </th>
                    @endforeach
                    <th class="text-center bg-secondary text-white" style="min-width:110px">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($metrics as $metric)
                <tr>
                    <td class="metric-label">{{ $metric['label'] }}</td>
                    <td class="text-center text-muted small fw-bold">{{ $metric['formula'] }}</td>
                    @foreach($area->mines as $mine)
                    @php
                        $val = $selectedMode === 'YEARLY'
                            ? ($yearlyData[$mine->id][$metric['key']] ?? null)
                            : ($costsheetRows[$mine->id]?->{$metric['key']} ?? null);
                    @endphp
                    <td class="text-end {{ $val !== null && $val < 0 ? 'negative-val' : '' }}">
                        {{ $val !== null ? number_format($val, 2) : '-' }}
                    </td>
                    @endforeach
                    @php $at = $areaCsvTotals[$area->id][$metric['key']] ?? null; @endphp
                    <td class="text-end fw-bold bg-light {{ $at !== null && $at < 0 ? 'negative-val' : '' }}">
                        {{ $at !== null ? number_format($at, 2) : '-' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endforeach

@elseif($selectedArea && $mines->count() > 0)

{{-- ── SINGLE AREA MODE ───────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-table me-2"></i>
            <strong>{{ $areas->firstWhere('id', $selectedArea)?->name }}</strong>
            &mdash; FY {{ fy_label((int)$selectedYear) }}
            &mdash; <span class="badge {{ $selectedMode==='YEARLY' ? 'bg-warning text-dark' : 'bg-primary' }}">
                {{ $selectedMode==='YEARLY' ? 'Full Year' : $selectedMode }}
            </span>
        </span>
        @if($selectedMode==='YEARLY')
        <small class="opacity-75">Yearly = sum of all available quarters</small>
        @endif
    </div>
    <div class="card-body p-0 overflow-auto">
        <table class="table table-bordered table-sm table-costsheet mb-0">
            <thead>
                <tr>
                    <th style="min-width:240px">DESCRIPTION</th>
                    <th style="min-width:60px" class="text-center">FML</th>
                    @foreach($mines as $mine)
                    <th class="text-center" style="min-width:110px">
                        {{ $mine->mine_code }}<br>
                        <span class="mine-name fw-normal">{{ $mine->mine_name }}</span>
                    </th>
                    @endforeach
                    <th class="text-center bg-secondary text-white" style="min-width:120px">TOTAL</th>
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
                    @php $total = $csvTotals[$metric['key']] ?? null; @endphp
                    <td class="text-end fw-bold bg-light {{ $total !== null && $total < 0 ? 'negative-val' : '' }}">
                        {{ $total !== null ? number_format($total, 2) : '-' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@elseif($selectedArea)
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>No data found for selected area / period.
    @role('super_admin|admin|area_admin')
    <a href="{{ route('upload.index') }}">Upload CSV</a>
    @endrole
</div>
@else
<div class="alert alert-secondary">
    <i class="bi bi-arrow-up-circle me-2"></i>Select an area to view costsheet data.
</div>
@endif
@endsection
