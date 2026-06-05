<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Mine;
use Illuminate\Http\Request;

class MineController extends Controller
{
    public function index(Request $request)
    {
        $areas = Area::orderBy('name')->get();
        $query = Mine::with('area')->orderBy('mine_code');

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('mine_code', 'like', "%{$search}%")
                  ->orWhere('mine_name', 'like', "%{$search}%");
            });
        }

        $mines = $query->paginate(25)->withQueryString();

        return view('mines.index', compact('mines', 'areas'));
    }

    public function create()
    {
        $areas = Area::where('is_active', true)->orderBy('name')->get();
        return view('mines.create', compact('areas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'mine_code' => 'required|string|max:20',
            'mine_name' => 'required|string|max:255',
            'mine_type' => 'required|in:OCM,UG,WASHERY,OTHER',
        ]);

        Mine::create([
            'area_id' => $request->area_id,
            'mine_code' => strtoupper($request->mine_code),
            'mine_name' => strtoupper($request->mine_name),
            'mine_type' => $request->mine_type,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('mines.index')->with('success', 'Mine created successfully.');
    }

    public function edit(Mine $mine)
    {
        $areas = Area::where('is_active', true)->orderBy('name')->get();
        return view('mines.edit', compact('mine', 'areas'));
    }

    public function update(Request $request, Mine $mine)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'mine_code' => 'required|string|max:20',
            'mine_name' => 'required|string|max:255',
            'mine_type' => 'required|in:OCM,UG,WASHERY,OTHER',
        ]);

        $mine->update([
            'area_id' => $request->area_id,
            'mine_code' => strtoupper($request->mine_code),
            'mine_name' => strtoupper($request->mine_name),
            'mine_type' => $request->mine_type,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('mines.index')->with('success', 'Mine updated successfully.');
    }

    public function destroy(Mine $mine)
    {
        if ($mine->costsheetData()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete mine with costsheet data.']);
        }
        $mine->delete();
        return redirect()->route('mines.index')->with('success', 'Mine deleted.');
    }
}
