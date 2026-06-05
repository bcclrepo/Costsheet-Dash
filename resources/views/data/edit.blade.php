@extends('layouts.app')
@section('title', 'Edit Mine Data')
@section('page-title', 'Edit Costsheet Data')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil me-2"></i>
                Edit: <strong>{{ $mine->mine_code }} - {{ $mine->mine_name }}</strong>
                ({{ $mine->area->name }}) &mdash; {{ $year }} / <span class="badge bg-primary">{{ $quarter }}</span>
            </div>
            <div class="card-body">
                <div class="alert alert-info py-2 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Enter raw values only. Derived metrics (Stripping Ratio, SPT, CPT, Costing Profit, Profit/Tonne) are computed automatically.
                </div>
                <form method="POST" action="{{ route('data.update', $mine->id) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="quarter" value="{{ $quarter }}">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Production Qty (TE) <small class="text-muted">Formula 1</small></label>
                            <input type="number" step="0.01" name="production_qty" class="form-control"
                                value="{{ old('production_qty', $record->production_qty) }}" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Dispatch Qty (TE) Excl. ST <small class="text-muted">Formula 2</small></label>
                            <input type="number" step="0.01" name="dispatch_qty" class="form-control"
                                value="{{ old('dispatch_qty', $record->dispatch_qty) }}" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">OBR Qty (M3) <small class="text-muted">Formula 3</small></label>
                            <input type="number" step="0.01" name="obr_qty" class="form-control"
                                value="{{ old('obr_qty', $record->obr_qty) }}" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Stripping Ratio <small>(Auto: OBR/Production)</small></label>
                            <input type="text" class="form-control bg-light" readonly
                                value="{{ $record->stripping_ratio ? number_format($record->stripping_ratio, 4) : 'Auto computed' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Net Sales (Lakhs) <small class="text-muted">Formula 5</small></label>
                            <input type="number" step="0.01" name="net_sales" class="form-control"
                                value="{{ old('net_sales', $record->net_sales) }}" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">SPT (Rs/Tonne) <small>(Auto: Net Sales/Dispatch×100000)</small></label>
                            <input type="text" class="form-control bg-light" readonly
                                value="{{ $record->spt ? number_format($record->spt, 2) : 'Auto computed' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Total Relevant Cost (Lakhs) <small class="text-muted">Formula 7</small></label>
                            <input type="number" step="0.01" name="total_relevant_cost" class="form-control"
                                value="{{ old('total_relevant_cost', $record->total_relevant_cost) }}" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">CPT (Rs/Tonne) <small>(Auto)</small></label>
                            <input type="text" class="form-control bg-light" readonly
                                value="{{ $record->cpt ? number_format($record->cpt, 2) : 'Auto computed' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Costing Profit (Lakhs) <small>(Auto: NetSales - TotalCost)</small></label>
                            <input type="text" class="form-control bg-light" readonly
                                value="{{ $record->costing_profit ? number_format($record->costing_profit, 2) : 'Auto computed' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Profit/Tonne (Rs) <small>(Auto)</small></label>
                            <input type="text" class="form-control bg-light" readonly
                                value="{{ $record->profit_per_tonne ? number_format($record->profit_per_tonne, 2) : 'Auto computed' }}">
                        </div>
                    </div>
                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Save Changes
                        </button>
                        <a href="{{ route('data.quarterly', ['area_id' => $mine->area_id, 'year' => $year, 'quarter' => $quarter]) }}"
                           class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
