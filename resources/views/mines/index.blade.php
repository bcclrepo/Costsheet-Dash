@extends('layouts.app')
@section('title', 'Mine Management')
@section('page-title', 'Mine Management')

@section('content')
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('mines.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <select name="area_id" class="form-select form-select-sm">
                    <option value="">All Areas</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search mine code or name..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Search</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('mines.create') }}" class="btn btn-success btn-sm w-100"><i class="bi bi-plus me-1"></i>Add Mine</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-geo-alt me-2"></i>Mines ({{ $mines->total() }})</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0 small" style="table-layout:auto">
            <thead class="table-dark">
                <tr><th>Code</th><th>Mine Name</th><th>Area</th><th>Type</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($mines as $mine)
                <tr>
                    <td><strong>{{ $mine->mine_code }}</strong></td>
                    <td>{{ $mine->mine_name }}</td>
                    <td><span class="badge bg-secondary">{{ $mine->area->name ?? '-' }}</span></td>
                    <td>
                        @php $tc = ['OCM'=>'primary','UG'=>'success','WASHERY'=>'info','OTHER'=>'secondary']; @endphp
                        <span class="badge bg-{{ $tc[$mine->mine_type] ?? 'secondary' }}">{{ $mine->mine_type }}</span>
                    </td>
                    <td><span class="badge {{ $mine->is_active ? 'bg-success' : 'bg-danger' }}">{{ $mine->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('mines.edit', $mine) }}" class="btn btn-xs btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('mines.destroy', $mine) }}"
                                onsubmit="return confirm('Delete {{ $mine->mine_name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No mines found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($mines->hasPages())<div class="card-footer">{{ $mines->links() }}</div>@endif
</div>
@endsection
