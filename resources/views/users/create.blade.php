@extends('layouts.app')
@section('title', 'Create User')
@section('page-title', 'Create New User')

@section('content')
<div class="row justify-content-center"><div class="col-md-8">
<div class="card">
    <div class="card-header"><i class="bi bi-person-plus me-2"></i>New User</div>
    <div class="card-body">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">PIS Number <span class="text-danger">*</span></label>
                    <input type="text" name="pis_number" class="form-control @error('pis_number') is-invalid @enderror"
                        value="{{ old('pis_number') }}" required placeholder="e.g. EMP12345">
                    @error('pis_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Mobile No</label>
                    <input type="text" name="mobile_no" class="form-control" value="{{ old('mobile_no') }}"
                        placeholder="+91 XXXXXXXXXX">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-select @error('role') is-invalid @enderror"
                        id="roleSelect" required>
                        <option value="">-- Select Role --</option>
                        @php $roleLabels = ['super_admin'=>'Super Admin','admin'=>'Admin','area_admin'=>'Area Admin','viewer'=>'Viewer']; @endphp
                        @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                            {{ $roleLabels[$role->name] ?? ucfirst($role->name) }}
                        </option>
                        @endforeach
                    </select>
                    <div class="form-text small mt-1">
                        <strong>Super Admin</strong>: Full access &bull;
                        <strong>Admin</strong>: Upload + view all areas &bull;
                        <strong>Area Admin</strong>: Assigned areas only &bull;
                        <strong>Viewer</strong>: View assigned areas only
                    </div>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <div class="form-check mt-3">
                        <input type="checkbox" name="is_active" class="form-check-input" id="ia" value="1"
                            {{ old('is_active','1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="ia">Active Account</label>
                    </div>
                </div>

                {{-- Area assignment (shown only for area_admin / viewer) --}}
                <div class="col-12" id="areaSection" style="display:none">
                    <label class="form-label fw-semibold">Assign Areas <span class="text-danger">*</span></label>
                    <div class="border rounded p-2" style="max-height:180px;overflow-y:auto">
                        @foreach($areas as $area)
                        <div class="form-check">
                            <input type="checkbox" name="areas[]" value="{{ $area->id }}"
                                class="form-check-input" id="area_{{ $area->id }}"
                                {{ in_array($area->id, old('areas',[]) ) ? 'checked' : '' }}>
                            <label class="form-check-label" for="area_{{ $area->id }}">{{ $area->name }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="d-flex gap-3 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Create User</button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
@push('scripts')
<script>
function toggleAreaSection() {
    var role = document.getElementById('roleSelect').value;
    var show = role === 'area_admin' || role === 'viewer';
    document.getElementById('areaSection').style.display = show ? '' : 'none';
}
document.getElementById('roleSelect').addEventListener('change', toggleAreaSection);
toggleAreaSection();
</script>
@endpush
