@extends('layouts.app')
@section('title', 'Add Area')
@section('page-title', 'Add New Area')
@section('content')
<div class="row justify-content-center"><div class="col-md-6">
<div class="card">
    <div class="card-header"><i class="bi bi-map me-2"></i>New Area</div>
    <div class="card-body">
        <form method="POST" action="{{ route('areas.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Area Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name') }}" required placeholder="e.g. KUSUNDA AREA">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Code</label>
                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="Optional code">
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="is_active" class="form-check-input" id="ia" value="1" checked>
                <label class="form-check-label" for="ia">Active</label>
            </div>
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Save Area</button>
                <a href="{{ route('areas.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
