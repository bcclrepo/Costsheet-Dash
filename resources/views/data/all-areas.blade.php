@extends('layouts.app')
@section('title', 'All Areas Costsheet')
@section('page-title', 'All Areas Costsheet')

@section('content')

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('data.all-areas') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold mb-1">Financial Year</label>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach(fy_years() as $startYear => $label)
                        <option value="{{ $startYear }}" {{ $selectedYear == $startYear ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-9">
                <label class="form-label fw-semibold mb-1">Period</label>
                <div class="btn-group w-100" role="group">
                    @foreach(['Q1'=>'Q1 (Apr-Jun)','Q2'=>'Q2 (Jul-Sep)','Q3'=>'Q3 (Oct-Dec)','Q4'=>'Q4 (Jan-Mar)','YEARLY'=>'Full Year'] as $mode => $label)
                    <a href="{{ route('data.all-areas', ['year'=>$selectedYear,'mode'=>$mode]) }}"
                       class="btn btn-sm {{ $selectedMode === $mode ? ($mode==='YEARLY' ? 'btn-warning' : 'btn-primary') : 'btn-outline-secondary' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>
            </div>
        </form>
    </div>
</div>

@foreach($areas as $area)
@if($area->mines->count() > 0)
<div class="card mb-3">
    <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-map me-2"></i><strong>{{ $area->name }}</strong>
            <span class="badge bg-light text-dark ms-2">{{ $area->mines->count() }} mines</span>
        </span>
    </div>
    <div class="card-body p-0 overflow-auto">
        <table class="table table-bordered table-sm table-costsheet mb-0">
            <thead>
                <tr>
                    <th style="min-width:200px">DESCRIPTION</th>
                    <th style="min-width:55px" class="text-center">FML</th>
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
                        $row = $data[$mine->id] ?? null;
                        $val = $row ? (is_array($row) ? ($row[$metric['key']] ?? null) : $row->{$metric['key']}) : null;
                    @endphp
                    <td class="text-end {{ $val !== null && $val < 0 ? 'negative-val' : '' }}">
                        {{ $val !== null ? number_format($val, 2) : '-' }}
                    </td>
                    @endforeach
                    @php $areaTotal = $areaCsvTotals[$area->id][$metric['key']] ?? null; @endphp
                    <td class="text-end fw-bold bg-light {{ $areaTotal !== null && $areaTotal < 0 ? 'negative-val' : '' }}">
                        {{ $areaTotal !== null ? number_format($areaTotal, 2) : '-' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endforeach
@endsection
