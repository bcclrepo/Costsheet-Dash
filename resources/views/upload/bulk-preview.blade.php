@extends('layouts.app')
@section('title', 'Bulk Preview')
@section('page-title', 'Bulk Upload Preview – Confirm')

@section('content')

@php
$metrics = app(\App\Http\Controllers\DataController::class)->getMetrics();
@endphp

{{-- Warnings --}}
@if(!empty($result['warnings']))
<div class="alert alert-danger">
    <strong><i class="bi bi-exclamation-circle me-1"></i>
        {{ count($result['warnings']) }} mine(s) NOT found in master data — will be skipped:
    </strong>
    <ul class="mb-0 mt-1 small">
        @foreach($result['warnings'] as $w)<li>{{ $w }}</li>@endforeach
    </ul>
</div>
@endif

{{-- Summary bar --}}
@php
    $totalMines  = count($result['mine_rows']);
    $validMines  = collect($result['mine_rows'])->filter(fn($r) => $r['mine'] !== null)->count();
    $skippedMines = $totalMines - $validMines;
    $existsMines = collect($result['mine_rows'])->filter(fn($r) => $r['existing'] !== null)->count();
@endphp
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card text-center py-2">
        <div class="fw-bold fs-4 text-primary">{{ $totalMines }}</div><div class="small text-muted">Total mines in CSV</div>
    </div></div>
    <div class="col-md-3"><div class="card text-center py-2">
        <div class="fw-bold fs-4 text-success">{{ $validMines }}</div><div class="small text-muted">Valid (in master)</div>
    </div></div>
    <div class="col-md-3"><div class="card text-center py-2">
        <div class="fw-bold fs-4 text-danger">{{ $skippedMines }}</div><div class="small text-muted">Skipped (not found)</div>
    </div></div>
    <div class="col-md-3"><div class="card text-center py-2">
        <div class="fw-bold fs-4 text-warning">{{ $existsMines }}</div><div class="small text-muted">Existing (will overwrite)</div>
    </div></div>
</div>

{{-- Per-area preview tables --}}
@foreach($result['area_preview'] as $areaId => $areaGroup)
<div class="card mb-3">
    <div class="card-header bg-dark text-white py-2">
        <i class="bi bi-map me-2"></i>
        <strong>{{ $areaGroup['area']->name }}</strong>
        <span class="badge bg-light text-dark ms-2">{{ count($areaGroup['mine_rows']) }} mines</span>
    </div>
    <div class="card-body p-0 overflow-auto">
        <table class="table table-bordered table-sm table-costsheet mb-0">
            <thead>
                <tr>
                    <th style="min-width:220px">DESCRIPTION</th>
                    <th class="text-center" style="min-width:55px">FML</th>
                    @foreach($areaGroup['mine_rows'] as $row)
                    <th class="text-center {{ $row['has_warning'] ? 'table-danger' : '' }}" style="min-width:120px">
                        <strong>{{ $row['mine_code'] }}</strong>
                        @if($row['mine'])
                            <br><small class="fw-normal">{{ Str::limit($row['mine']->mine_name, 20) }}</small>
                        @else
                            <br><small class="text-danger fw-bold">NOT IN DB</small>
                        @endif
                        @if($row['existing'])
                            <br><span class="badge bg-warning text-dark">EXISTS</span>
                        @endif
                        @if($row['no_data'])
                            <br><span class="badge bg-secondary">NO DATA</span>
                        @endif
                    </th>
                    @endforeach
                    <th class="text-center bg-secondary text-white" style="min-width:110px">AREA TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($metrics as $metric)
                <tr>
                    <td class="metric-label">{{ $metric['label'] }}</td>
                    <td class="text-center text-muted small fw-bold">{{ $metric['formula'] }}</td>
                    @foreach($areaGroup['mine_rows'] as $row)
                    @php $val = $row['data'][$metric['key']] ?? null; @endphp
                    <td class="text-end {{ $val !== null && $val < 0 ? 'negative-val' : '' }}
                               {{ $row['has_warning'] ? 'table-danger' : '' }}">
                        {{ $val !== null ? number_format($val, 2) : '-' }}
                    </td>
                    @endforeach
                    @php $at = $result['area_totals'][$areaId][$metric['key']] ?? null; @endphp
                    <td class="text-end fw-bold bg-light {{ $at !== null && $at < 0 ? 'negative-val' : '' }}">
                        {{ $at !== null ? number_format($at, 2) : '-' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach

{{-- Action bar --}}
<div class="d-flex justify-content-between align-items-center mt-3">
    <a href="{{ route('upload.bulk.index') }}" class="btn btn-secondary btn-lg">
        <i class="bi bi-arrow-left me-2"></i>Cancel / Re-upload
    </a>
    <form action="{{ route('upload.bulk.store') }}" method="POST" id="bulkConfirmForm">
        @csrf
        <input type="hidden" name="overwrite" value="1">
        <button type="submit" id="bulkConfirmBtn" class="btn btn-success btn-lg">
            <i class="bi bi-check-circle me-2"></i>
            Confirm & Save {{ $validMines }} Mines
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
var bulkSubmitting = false;
document.getElementById('bulkConfirmForm').addEventListener('submit', function(e) {
    if (bulkSubmitting) return;
    e.preventDefault();
    var form = this;
    Swal.fire({
        title: 'Confirm Bulk Upload?',
        html: 'Save <strong>{{ $validMines }}</strong> mines for '
            + 'FY <strong>{{ fy_label((int)$year) }}</strong> / <strong>{{ $quarter }}</strong>?'
            + (@if($existsMines > 0) '<br><span class="text-warning">⚠ {{ $existsMines }} existing records will be overwritten.</span>' @else '' @endif),
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Save All!'
    }).then(function(r) {
        if (r.isConfirmed) { bulkSubmitting = true; form.submit(); }
    });
});
</script>
@endpush
