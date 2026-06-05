@extends('layouts.app')
@section('title', 'Upload CSV')
@section('page-title', 'Upload Costsheet CSV')

@section('content')
<div class="row g-4">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-cloud-upload me-2"></i>Upload New CSV File
            </div>
            <div class="card-body">
                <div class="alert alert-info py-2 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Upload area-wise CSV data quarterly. You will preview the data before saving.
                    <a href="{{ route('upload.template') }}" class="fw-semibold">Download Template</a>
                </div>
                <form action="{{ route('upload.preview') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Area <span class="text-danger">*</span></label>
                        @if($autoArea)
                        {{-- Single-area user: locked field --}}
                        <input type="hidden" name="area_id" value="{{ $autoArea->id }}">
                        <div class="form-control bg-light fw-semibold">
                            <i class="bi bi-map me-1 text-primary"></i>{{ $autoArea->name }}
                        </div>
                        @else
                        <select name="area_id" class="form-select" required>
                            <option value="">-- Select Area --</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </select>
                        @endif
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Financial Year <span class="text-danger">*</span></label>
                                <select name="year" class="form-select" required>
                                    @foreach(fy_years() as $startYear => $label)
                                        <option value="{{ $startYear }}" {{ old('year', fy_start()) == $startYear ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Quarter <span class="text-danger">*</span></label>
                                <select name="quarter" class="form-select" required>
                                    <option value="Q1" {{ old('quarter') == 'Q1' ? 'selected' : '' }}>Q1 (Apr-Jun)</option>
                                    <option value="Q2" {{ old('quarter') == 'Q2' ? 'selected' : '' }}>Q2 (Jul-Sep)</option>
                                    <option value="Q3" {{ old('quarter') == 'Q3' ? 'selected' : '' }}>Q3 (Oct-Dec)</option>
                                    <option value="Q4" {{ old('quarter') == 'Q4' ? 'selected' : '' }}>Q4 (Jan-Mar)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                        <div class="form-text">Only CSV files. Max 5MB.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-eye me-2"></i>Preview Data
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2"></i>Upload History</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 small" id="historyTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Area</th>
                            <th>Year</th>
                            <th>Qtr</th>
                            <th>Imported</th>
                            <th>Skipped</th>
                            <th>By</th>
                            <th>Date</th>
                            <th>File</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($uploadHistory as $uf)
                        <tr>
                            <td>{{ $uf->area->name ?? '-' }}</td>
                            <td>{{ fy_label((int)$uf->year) }}</td>
                            <td><span class="badge bg-primary">{{ $uf->quarter }}</span></td>
                            <td><span class="badge bg-success">{{ $uf->rows_imported }}</span></td>
                            <td><span class="badge bg-secondary">{{ $uf->rows_skipped }}</span></td>
                            <td>{{ $uf->uploader->name ?? '-' }}</td>
                            <td>{{ $uf->created_at->format('d/m/y H:i') }}</td>
                            <td class="text-truncate" style="max-width:120px" title="{{ $uf->original_filename }}">
                                {{ $uf->original_filename }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($uploadHistory->hasPages())
            <div class="card-footer">{{ $uploadHistory->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('#historyTable').DataTable({ paging: false, searching: false, info: false, order: [] });
</script>
@endpush
