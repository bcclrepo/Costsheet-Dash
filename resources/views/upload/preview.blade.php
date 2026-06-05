@extends('layouts.app')
@section('title', 'Preview Upload')
@section('page-title', 'Preview CSV Data - Confirm Upload')

@section('content')

{{-- Existing data warning --}}
@if($existingUpload)
<div class="alert alert-warning d-flex align-items-start gap-3">
    <i class="bi bi-exclamation-triangle-fill fs-4 mt-1"></i>
    <div>
        <strong>Data Already Exists!</strong><br>
        Data for <strong>{{ $area->name }}</strong> &mdash;
        <strong>FY {{ fy_label((int)$year) }} / {{ $quarter }}</strong>
        was previously uploaded on
        <strong>{{ $existingUpload->created_at->format('d M Y H:i') }}</strong>
        by <strong>{{ $existingUpload->uploader->name ?? 'Unknown' }}</strong>.<br>
        <span class="text-danger fw-semibold">Confirming will overwrite existing data for all matching mines.</span>
    </div>
</div>
@endif

{{-- Parse warnings --}}
@if(!empty($preview['warnings']))
<div class="alert alert-danger">
    <strong><i class="bi bi-exclamation-circle me-1"></i>Warnings ({{ count($preview['warnings']) }}):</strong>
    <ul class="mb-0 mt-1">
        @foreach($preview['warnings'] as $w)
            <li>{{ $w }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card mb-3">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>
            <i class="bi bi-table me-2"></i>
            Preview: <strong>{{ $area->name }}</strong> &mdash;
            FY {{ fy_label((int)$year) }} / <span class="badge bg-primary">{{ $quarter }}</span>
        </span>
        <span class="badge bg-light text-dark fs-6">{{ count($preview['rows']) }} mines in CSV</span>
    </div>
    <div class="card-body p-0 overflow-auto">
        <table class="table table-bordered table-sm table-costsheet mb-0">
            <thead>
                <tr>
                    <th style="min-width:220px">DESCRIPTION</th>
                    <th style="min-width:90px" class="text-center">FORMULA</th>
                    @foreach($preview['rows'] as $row)
                    <th class="text-center {{ $row['has_warning'] ? 'table-danger' : '' }}" style="min-width:130px">
                        <span class="fw-bold">{{ $row['mine_code'] }}</span>
                        @if($row['mine'])
                            <br><small class="fw-normal">{{ Str::limit($row['mine']->mine_name, 22) }}</small>
                        @else
                            <br><small class="text-danger fw-bold">NOT IN DB</small>
                        @endif
                        @if($row['existing'])
                            <br><span class="badge bg-warning text-dark small">EXISTS</span>
                        @endif
                    </th>
                    @endforeach
                    <th class="text-center bg-dark text-white" style="min-width:130px">TOTAL<br><small class="fw-normal opacity-75">(from CSV)</small></th>
                </tr>
            </thead>
            <tbody>
                @php
                $metrics = [
                    ['key'=>'production_qty',      'label'=>'PRODUCTION QTY (TE)',                    'formula'=>'1'],
                    ['key'=>'dispatch_qty',         'label'=>'DISPATCH QTY (TE) (Excl. ST)',           'formula'=>'2'],
                    ['key'=>'obr_qty',              'label'=>'OBR QTY (M3)',                           'formula'=>'3'],
                    ['key'=>'stripping_ratio',      'label'=>'STRIPPING RATIO',                        'formula'=>'4=3/1'],
                    ['key'=>'net_sales',            'label'=>'NET SALES (LAKHS)',                      'formula'=>'5'],
                    ['key'=>'spt',                  'label'=>'SPT (RS. PER TONNE)',                    'formula'=>'6=5/2'],
                    ['key'=>'total_relevant_cost',  'label'=>'TOTAL RELEVANT COST (LAKHS)',            'formula'=>'7'],
                    ['key'=>'cpt',                  'label'=>'CPT (RS. PER TONNE)',                    'formula'=>'8=7/2'],
                    ['key'=>'costing_profit',       'label'=>'COSTING PROFIT (LAKHS)',                 'formula'=>'9=5-7'],
                    ['key'=>'profit_per_tonne',     'label'=>'PROFIT (RS. PER TONNE)',                 'formula'=>'10=9/2'],
                ];
                @endphp
                @foreach($metrics as $metric)
                <tr>
                    <td class="metric-label">{{ $metric['label'] }}</td>
                    <td class="text-center text-muted small">{{ $metric['formula'] }}</td>
                    @foreach($preview['rows'] as $row)
                    @php $val = $row['data'][$metric['key']] ?? null; @endphp
                    <td class="text-end {{ $val !== null && $val < 0 ? 'negative-val' : '' }}
                               {{ $row['has_warning'] ? 'table-danger' : '' }}">
                        {{ $val !== null ? number_format($val, 2) : '-' }}
                    </td>
                    @endforeach
                    {{-- TOTAL: read directly from CSV last column --}}
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

<div class="d-flex gap-3 justify-content-between align-items-center">
    <a href="{{ route('upload.index') }}" class="btn btn-secondary btn-lg">
        <i class="bi bi-arrow-left me-2"></i>Cancel / Re-upload
    </a>

    {{-- Session holds area/year/quarter/filename — only overwrite flag comes from form --}}
    <form action="{{ route('upload.store') }}" method="POST" id="confirmForm">
        @csrf
        @if($existingUpload)
            <input type="hidden" name="overwrite" value="1">
        @endif
        {{-- type=submit works without JS; SweetAlert intercepts the submit event --}}
        <button type="submit" id="confirmBtn"
                class="btn btn-lg {{ $existingUpload ? 'btn-danger' : 'btn-success' }}">
            <i class="bi bi-check-circle me-2"></i>
            {{ $existingUpload ? 'Confirm & Overwrite' : 'Confirm & Save' }}
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
var formSubmitting = false;

document.getElementById('confirmForm').addEventListener('submit', function (e) {
    if (formSubmitting) return; // already confirmed — let it go
    e.preventDefault();

    var form = this;

    @if($existingUpload)
    Swal.fire({
        title: 'Overwrite Existing Data?',
        html: 'Data for <strong>{{ addslashes($area->name) }}</strong><br>'
            + 'FY <strong>{{ fy_label((int)$year) }}</strong> / <strong>{{ $quarter }}</strong><br>'
            + 'already exists. <span class="text-danger fw-bold">This will overwrite it!</span>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Overwrite!',
        cancelButtonText: 'Cancel'
    }).then(function (result) {
        if (result.isConfirmed) {
            formSubmitting = true;
            form.submit();
        }
    });
    @else
    Swal.fire({
        title: 'Confirm Upload?',
        html: 'Save costsheet data for <strong>{{ addslashes($area->name) }}</strong><br>'
            + 'FY <strong>{{ fy_label((int)$year) }}</strong> / <strong>{{ $quarter }}</strong>?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Save!'
    }).then(function (result) {
        if (result.isConfirmed) {
            formSubmitting = true;
            form.submit();
        }
    });
    @endif
});
</script>
@endpush
