@extends('layouts.app')
@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-2"></i>All Users ({{ $users->total() }})</span>
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i>Add User
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0 small" id="usersTable">
            <thead class="table-dark">
                <tr>
                    <th>#</th><th>Name</th><th>PIS</th><th>Email</th><th>Mobile</th>
                    <th>Role</th><th>Assigned Areas</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <i class="bi bi-person-circle me-1 text-secondary"></i>{{ $user->name }}
                        @if($user->id === auth()->id()) <span class="badge bg-primary ms-1">You</span> @endif
                    </td>
                    <td><code>{{ $user->pis_number ?? '-' }}</code></td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->mobile_no ?? '-' }}</td>
                    <td>
                        @php $role = $user->roles->first()?->name ?? 'viewer'; @endphp
                        @php $rc = ['super_admin'=>'danger','admin'=>'primary','area_admin'=>'info','viewer'=>'secondary']; @endphp
                        <span class="badge bg-{{ $rc[$role] ?? 'secondary' }}">{{ ucwords(str_replace('_',' ',$role)) }}</span>
                    </td>
                    <td>
                        @if(in_array($role, ['area_admin','viewer']))
                            @forelse($user->areas as $a)
                                <span class="badge bg-light text-dark border">{{ $a->name }}</span>
                            @empty
                                <span class="text-muted">None</span>
                            @endforelse
                        @else
                            <span class="text-muted small">All areas</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.toggle-active', $user) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }}"
                                    title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="bi bi-{{ $user->is_active ? 'pause' : 'play' }}"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('users.destroy', $user) }}"
                                onsubmit="return confirm('Delete {{ addslashes($user->name) }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($users->hasPages())<div class="card-footer">{{ $users->links() }}</div>@endif
</div>
@endsection
@push('scripts')
<script>$('#usersTable').DataTable({ paging: false, order: [] });</script>
@endpush
