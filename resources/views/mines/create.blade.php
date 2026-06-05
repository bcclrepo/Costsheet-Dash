@extends('layouts.app')
@section('title', 'Add Mine')
@section('page-title', 'Add New Mine')
@section('content')
<div class="row justify-content-center"><div class="col-md-6">
<div class="card">
    <div class="card-header"><i class="bi bi-geo-alt me-2"></i>New Mine</div>
    <div class="card-body">
        <form method="POST" action="{{ route('mines.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Area <span class="text-danger">*</span></label>
                <select name="area_id" class="form-select @error('area_id') is-invalid @enderror" required>
                    <option value="">-- Select Area --</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                    @endforeach
                </select>
                @error('area_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Mine Code <span class="text-danger">*</span></label>
                    <input type="text" name="mine_code" class="form-control @error('mine_code') is-invalid @enderror"
                        value="{{ old('mine_code') }}" placeholder="e.g. 2026" required>
                    @error('mine_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                    <select name="mine_type" class="form-select" required>
                        <option value="OCM" {{ old('mine_type') == 'OCM' ? 'selected' : '' }}>OCM</option>
                        <option value="UG" {{ old('mine_type') == 'UG' ? 'selected' : '' }}>UG</option>
                        <option value="WASHERY" {{ old('mine_type') == 'WASHERY' ? 'selected' : '' }}>Washery</option>
                        <option value="OTHER" {{ old('mine_type') == 'OTHER' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="ia" value="1" checked>
                        <label class="form-check-label" for="ia">Active</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Mine Name <span class="text-danger">*</span></label>
                <input type="text" name="mine_name" class="form-control @error('mine_name') is-invalid @enderror"
                    value="{{ old('mine_name') }}" placeholder="e.g. AMLG. DHANSAR INDUSTRY OCM" required>
                @error('mine_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Save Mine</button>
                <a href="{{ route('mines.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
