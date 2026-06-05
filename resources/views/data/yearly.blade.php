@extends('layouts.app')
@section('title', 'Yearly Summary')
@section('page-title', 'Yearly Costsheet Summary')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('data.yearly') }}" class="row g-3 align-items-end">
            <div class="col-md-5">
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
            <div class="col-md-3">
                <label class="form-label fw-semibold">Financial Year</label>
                <select name="year" class="form-select">
                    @foreach(fy_years() as $startYear => $label)
                        <option value="{{ $startYear }}" {{ $selectedYear == $startYear ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100"><i class="bi bi-search me-1"></i>View Yearly</button>
            </div>
        </form>
    </div>
</div>

@if($selectedArea && count($mines) > 0)
<div class="alert alert-info py-2 small">
    <i class="bi bi-info-circle me-1"></i>
    Yearly data is the <strong>summed total</strong> of all available quarterly data for {{ $selectedYear }}.
    Derived metrics (ratios, per-tonne) are recomputed from yearly totals.
</div>
<div class="card">
    <div class="card-header">
        <i class="bi bi-table me-2"></i>
        {{ $areas->firstWhere('id', $selectedArea)?->name }} &mdash; FY {{ fy_label((int)$selectedYear) }}
    </div>
    <div class="card-body p-0 overflow-auto">
        <table class="table table-bordered table-sm table-costsheet mb-0">
            <thead>
                <tr>
                    <th style="min-width:240px">DESCRIPTION</th>
                    <th style="min-width:100px" class="text-center">FORMULA</th>
                    @foreach($mines as $mine)
                    <th class="text-center" style="min-width:140px">
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
                    @php $val = $yearlyData[$mine->id][$metric['key']] ?? null; @endphp
                    <td class="text-end {{ $val !== null && $val < 0 ? 'negative-val' : '' }}">
                        {{ $val !== null ? number_format($val, 2) : '-' }}
                    </td>
                    @endforeach
                    {{-- TOTAL: summed from per-quarter CSV totals, not from mine values --}}
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
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No data found for selected area/year.</div>
@else
<div class="alert alert-secondary"><i class="bi bi-arrow-up-circle me-2"></i>Please select an area and year to view yearly summary.</div>
@endif
@endsection
