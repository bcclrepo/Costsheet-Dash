@extends('layouts.app')
@section('title', 'Bulk Upload – All Areas')
@section('page-title', 'Bulk Upload – All Areas (Single CSV)')

@section('content')
<div class="row g-4">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-cloud-upload me-2"></i>Upload All-Areas CSV</div>
            <div class="card-body">
                <div class="alert alert-info py-2 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Upload a single CSV containing <strong>all mines across all areas</strong>.
                    Mine codes are validated against master data — unknown mines are skipped and reported.
                    <a href="{{ route('upload.bulk.template') }}" class="fw-semibold">Download Template</a>
                </div>
                <form action="{{ route('upload.bulk.preview') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Financial Year <span class="text-danger">*</span></label>
                            <select name="year" class="form-select" required>
                                @foreach(fy_years() as $startYear => $label)
                                    <option value="{{ $startYear }}" {{ old('year', fy_start()) == $startYear ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Quarter <span class="text-danger">*</span></label>
                            <select name="quarter" class="form-select" required>
                                <option value="Q1">Q1 (Apr-Jun)</option>
                                <option value="Q2">Q2 (Jul-Sep)</option>
                                <option value="Q3">Q3 (Oct-Dec)</option>
                                <option value="Q4">Q4 (Jan-Mar)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                        <div class="form-text">Max 20 MB.</div>
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
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Bulk Upload History</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 small">
                    <thead class="table-dark">
                        <tr><th>Area</th><th>Year</th><th>Qtr</th><th>Imported</th><th>By</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        @forelse($uploadHistory as $uf)
                        <tr>
                            <td>{{ $uf->area->name ?? '-' }}</td>
                            <td>{{ fy_label((int)$uf->year) }}</td>
                            <td><span class="badge bg-primary">{{ $uf->quarter }}</span></td>
                            <td><span class="badge bg-success">{{ $uf->rows_imported }}</span></td>
                            <td>{{ $uf->uploader->name ?? '-' }}</td>
                            <td>{{ $uf->created_at->format('d/m/y H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No bulk uploads yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
