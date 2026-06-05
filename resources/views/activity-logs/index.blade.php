@extends('layouts.app')
@section('title', 'Activity Log Monitor')
@section('page-title', 'Activity Log Monitor')

@section('content')

{{-- Filter bar --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('activity-logs.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label fw-semibold mb-1">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    @foreach($actions as $a)
                    <option value="{{ $a }}" {{ request('action') == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold mb-1">User</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">All Users</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }} ({{ $u->pis_number }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold mb-1">Area</label>
                <input type="text" name="area" class="form-control form-control-sm"
                    value="{{ request('area') }}" placeholder="Area name">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold mb-1">FY Year</label>
                <select name="year" class="form-select form-select-sm">
                    <option value="">All Years</option>
                    @foreach(fy_years() as $y => $l)
                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label fw-semibold mb-1">Quarter</label>
                <select name="quarter" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(['Q1','Q2','Q3','Q4'] as $q)
                    <option value="{{ $q }}" {{ request('quarter') == $q ? 'selected' : '' }}>{{ $q }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label fw-semibold mb-1">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
            </div>
            <div class="col-md-1">
                <label class="form-label fw-semibold mb-1">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i></button>
                <a href="{{ route('activity-logs.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-journal-text me-2"></i>Activity Logs ({{ $logs->total() }} records)</span>
                <span class="badge bg-secondary">Page {{ $logs->currentPage() }} / {{ $logs->lastPage() }}</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0 small">
                    <thead class="table-dark">
                        <tr>
                            <th style="min-width:140px">Timestamp</th>
                            <th>Action</th>
                            <th>User (PIS)</th>
                            <th>Description</th>
                            <th>IP / Client</th>
                            <th>Area</th>
                            <th>Mine</th>
                            <th>FY / Qtr</th>
                            <th>Changes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        @php
                            $actionColors = [
                                'CREATE'=>'success','UPDATE'=>'warning','DELETE'=>'danger',
                                'UPLOAD'=>'primary','LOGIN'=>'info','LOGOUT'=>'secondary',
                                'LOGIN_FAILED'=>'danger',
                            ];
                            $color = $actionColors[$log->action] ?? 'secondary';
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $log->created_at->format('d-m-Y H:i:s') }}</td>
                            <td><span class="badge bg-{{ $color }}">{{ $log->action }}</span></td>
                            <td>
                                <strong>{{ $log->user_name }}</strong><br>
                                <code class="small">{{ $log->pis_number }}</code>
                            </td>
                            <td>{{ $log->description }}</td>
                            <td class="small">
                                <code class="text-dark">{{ $log->ip_address ?? '-' }}</code>
                                @if($log->browser || $log->platform)
                                <br>
                                <span class="text-muted" title="{{ $log->user_agent }}">
                                    <i class="bi bi-{{ $log->device === 'Mobile' ? 'phone' : ($log->device === 'Tablet' ? 'tablet' : 'pc-display') }}"></i>
                                    {{ $log->browser }} / {{ $log->platform }}
                                </span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $log->area_name ?? '-' }}</td>
                            <td><code>{{ $log->mine_code ?? '-' }}</code></td>
                            <td class="text-muted">
                                @if($log->year)
                                    FY{{ $log->year }}-{{ $log->year+1 }}<br>{{ $log->quarter }}
                                @else -
                                @endif
                            </td>
                            <td>
                                @if($log->changes)
                                <button class="btn btn-xs btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal" data-bs-target="#changesModal"
                                    data-changes="{{ json_encode($log->changes) }}"
                                    data-desc="{{ $log->description }}">
                                    <i class="bi bi-eye"></i> {{ count($log->changes) }} field(s)
                                </button>
                                @else -
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No activity logs found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
            <div class="card-footer">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Quarterly log files panel --}}
    <div class="col-md-3">
        <div class="card">
            <div class="card-header"><i class="bi bi-file-text me-2"></i>Quarterly Log Files</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($logFiles as $f)
                    @php $fname = basename($f); @endphp
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="small"><i class="bi bi-file-earmark-text me-1 text-muted"></i>{{ $fname }}</span>
                        <a href="{{ route('activity-logs.download', ['file' => $fname]) }}"
                           class="btn btn-xs btn-sm btn-outline-primary" title="Download">
                            <i class="bi bi-download"></i>
                        </a>
                    </li>
                    @empty
                    <li class="list-group-item text-muted small py-3 text-center">No log files yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Changes detail modal --}}
<div class="modal fade" id="changesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Field Changes</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3" id="changesDesc"></p>
                <table class="table table-sm table-bordered" id="changesTable">
                    <thead class="table-dark">
                        <tr><th>Field</th><th>Previous Value</th><th>New Value</th></tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('changesModal').addEventListener('show.bs.modal', function(e) {
    var btn     = e.relatedTarget;
    var changes = JSON.parse(btn.getAttribute('data-changes') || '{}');
    var desc    = btn.getAttribute('data-desc') || '';
    document.getElementById('changesDesc').textContent = desc;
    var tbody   = document.querySelector('#changesTable tbody');
    tbody.innerHTML = '';
    Object.entries(changes).forEach(function([field, diff]) {
        var old_ = diff && typeof diff === 'object' ? (diff.old ?? '') : diff;
        var new_ = diff && typeof diff === 'object' ? (diff.new ?? '') : '';
        var tr = '<tr>'
            + '<td><code>' + field + '</code></td>'
            + '<td class="text-danger">' + (old_ !== null && old_ !== '' ? old_ : '<em class="text-muted">empty</em>') + '</td>'
            + '<td class="text-success">' + (new_ !== null && new_ !== '' ? new_ : '<em class="text-muted">empty</em>') + '</td>'
            + '</tr>';
        tbody.innerHTML += tr;
    });
});
</script>
@endpush
