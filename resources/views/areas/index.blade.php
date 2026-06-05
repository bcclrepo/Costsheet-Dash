@extends('layouts.app')
@section('title', 'Area Management')
@section('page-title', 'Area Management')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-map me-2"></i>All Areas</span>
        <a href="{{ route('areas.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus me-1"></i>Add Area</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="areasTable">
            <thead class="table-dark">
                <tr><th>#</th><th>Area Name</th><th>Code</th><th>Mines</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach($areas as $area)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $area->name }}</strong></td>
                    <td>{{ $area->code ?: '-' }}</td>
                    <td><span class="badge bg-info">{{ $area->mines_count }}</span></td>
                    <td><span class="badge {{ $area->is_active ? 'bg-success' : 'bg-danger' }}">{{ $area->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('areas.edit', $area) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('areas.destroy', $area) }}"
                                onsubmit="return confirm('Delete {{ $area->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($areas->hasPages())<div class="card-footer">{{ $areas->links() }}</div>@endif
</div>
@endsection
@push('scripts')
<script>$('#areasTable').DataTable({ paging: false, order: [] });</script>
@endpush
