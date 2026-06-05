@extends('layouts.app')
@section('title', 'Edit Mine')
@section('page-title', 'Edit Mine')
@section('content')
<div class="row justify-content-center"><div class="col-md-6">
<div class="card">
    <div class="card-header"><i class="bi bi-pencil me-2"></i>Edit: {{ $mine->mine_code }} - {{ $mine->mine_name }}</div>
    <div class="card-body">
        <form method="POST" action="{{ route('mines.update', $mine) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Area <span class="text-danger">*</span></label>
                <select name="area_id" class="form-select" required>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ old('area_id', $mine->area_id) == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Mine Code <span class="text-danger">*</span></label>
                    <input type="text" name="mine_code" class="form-control" value="{{ old('mine_code', $mine->mine_code) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Type</label>
                    <select name="mine_type" class="form-select">
                        @foreach(['OCM','UG','WASHERY','OTHER'] as $t)
                        <option value="{{ $t }}" {{ old('mine_type', $mine->mine_type) == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="ia" value="1"
                            {{ old('is_active', $mine->is_active ? '1' : '') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="ia">Active</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Mine Name <span class="text-danger">*</span></label>
                <input type="text" name="mine_name" class="form-control" value="{{ old('mine_name', $mine->mine_name) }}" required>
            </div>
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Update</button>
                <a href="{{ route('mines.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
