@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card blue">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small opacity-75">Total Areas</div>
                    <div class="fs-2 fw-bold">{{ $totalAreas }}</div>
                </div>
                <i class="bi bi-map fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card green">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small opacity-75">Total Mines</div>
                    <div class="fs-2 fw-bold">{{ $totalMines }}</div>
                </div>
                <i class="bi bi-geo-alt fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card orange">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small opacity-75">CSV Uploads</div>
                    <div class="fs-2 fw-bold">{{ $totalUploads }}</div>
                </div>
                <i class="bi bi-cloud-upload fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card red">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small opacity-75">Current Year</div>
                    <div class="fs-2 fw-bold">{{ date('Y') }}</div>
                </div>
                <i class="bi bi-calendar-check fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2"></i>Recent Uploads</span>
                @role('admin|uploader|modifier')
                <a href="{{ route('upload.index') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus"></i> Upload CSV
                </a>
                @endrole
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Area</th>
                            <th>Year</th>
                            <th>Quarter</th>
                            <th>Rows</th>
                            <th>Uploaded By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentUploads as $upload)
                        <tr>
                            <td>{{ $upload->area->name ?? '-' }}</td>
                            <td>{{ fy_label((int)$upload->year) }}</td>
                            <td><span class="badge bg-primary">{{ $upload->quarter }}</span></td>
                            <td><span class="badge bg-success">{{ $upload->rows_imported }}</span></td>
                            <td>{{ $upload->uploader->name ?? '-' }}</td>
                            <td class="text-muted small">{{ $upload->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No uploads yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-lightning-charge me-2"></i>Quick Access</div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('data.view') }}" class="btn btn-outline-primary">
                    <i class="bi bi-table me-2"></i>Costsheet View
                </a>
                @role('super_admin|admin')
                <a href="{{ route('data.all-areas') }}" class="btn btn-outline-info">
                    <i class="bi bi-grid-3x3-gap me-2"></i>All Areas Costsheet
                </a>
                @endrole
                @role('super_admin|admin|area_admin')
                <a href="{{ route('upload.index') }}" class="btn btn-outline-warning">
                    <i class="bi bi-cloud-upload me-2"></i>Upload CSV
                </a>
                @endrole
                @role('super_admin|admin')
                <a href="{{ route('upload.bulk.index') }}" class="btn btn-outline-success">
                    <i class="bi bi-cloud-arrow-up me-2"></i>Upload CSV (All Areas)
                </a>
                @endrole
            </div>
        </div>
    </div>
</div>
@endsection
