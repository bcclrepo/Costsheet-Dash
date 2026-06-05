@extends('layouts.app')
@section('title', 'Quarterly Data')
@section('page-title', 'Quarterly Costsheet Data')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('data.quarterly') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Area</label>
                <select name="area_id" class="form-select" required>
                    <option value="">-- Select Area --</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ $selectedArea == $area->id ? 'selected' : '' }}>
                            {{ $area->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Financial Year</label>
                <select name="year" class="form-select">
                    @foreach(fy_years() as $startYear => $label)
                        <option value="{{ $startYear }}" {{ $selectedYear == $startYear ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Quarter</label>
                <select name="quarter" class="form-select">
                    <option value="Q1" {{ $selectedQuarter == 'Q1' ? 'selected' : '' }}>Q1 (Apr-Jun)</option>
                    <option value="Q2" {{ $selectedQuarter == 'Q2' ? 'selected' : '' }}>Q2 (Jul-Sep)</option>
                    <option value="Q3" {{ $selectedQuarter == 'Q3' ? 'selected' : '' }}>Q3 (Oct-Dec)</option>
                    <option value="Q4" {{ $selectedQuarter == 'Q4' ? 'selected' : '' }}>Q4 (Jan-Mar)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>View</button>
            </div>
            @role('admin|modifier')
            @if($selectedArea && count($mines) > 0)
            <div class="col-md-2">
                <a href="{{ route('data.edit', ['mine' => $mines->first()->id ?? 0, 'year' => $selectedYear, 'quarter' => $selectedQuarter]) }}"
                   class="btn btn-outline-warning w-100">
                   <i class="bi bi-pencil me-1"></i>Edit
                </a>
            </div>
            @endif
            @endrole
        </form>
    </div>
</div>

@if($selectedArea && count($mines) > 0)
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-table me-2"></i>
            {{ $areas->firstWhere('id', $selectedArea)?->name }} &mdash;
            FY {{ fy_label((int)$selectedYear) }} / <span class="badge bg-primary">{{ $selectedQuarter }}</span>
        </span>
        @role('admin|modifier')
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-pencil me-1"></i>Edit Mine Data
            </button>
            <ul class="dropdown-menu">
                @foreach($mines as $mine)
                <li>
                    <a class="dropdown-item small" href="{{ route('data.edit', ['mine' => $mine->id, 'year' => $selectedYear, 'quarter' => $selectedQuarter]) }}">
                        {{ $mine->mine_code }} - {{ $mine->mine_name }}
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
        @endrole
    </div>
    <div class="card-body p-0 overflow-auto">
        <table class="table table-bordered table-sm table-costsheet mb-0">
            <thead>
                <tr>
                    <th style="min-width:240px">DESCRIPTION</th>
                    <th style="min-width:100px" class="text-center">FORMULA</th>
                    @foreach($mines as $mine)
                    <th class="text-center" style="min-width:130px">
                        {{ $mine->mine_code }}<br>
                        <span class="mine-name fw-normal">{{ $mine->mine_name }}</span>
                    </th>
                    @endforeach
                    <th class="text-center bg-dark text-white" style="min-width:130px">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($metrics as $metric)
                <tr>
                    <td class="metric-label">{{ $metric['label'] }}</td>
                    <td class="text-center text-muted small">{{ $metric['formula'] }}</td>
                    @foreach($mines as $mine)
                    @php
                        $data = $costsheetRows[$mine->id] ?? null;
                        $val  = $data?->{$metric['key']};
                    @endphp
                    <td class="text-end {{ $val !== null && $val < 0 ? 'negative-val' : '' }}">
                        {{ $val !== null ? number_format($val, 2) : '-' }}
                    </td>
                    @endforeach
                    {{-- TOTAL: from CSV, not calculated --}}
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
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No data found for selected area/year/quarter.</div>
@else
<div class="alert alert-secondary"><i class="bi bi-arrow-up-circle me-2"></i>Please select an area and quarter to view data.</div>
@endif
@endsection
