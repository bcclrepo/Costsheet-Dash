<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        $areas = Area::withCount('mines')->orderBy('name')->paginate(20);
        return view('areas.index', compact('areas'));
    }

    public function create()
    {
        return view('areas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:areas,name',
            'code' => 'nullable|string|max:50',
        ]);

        Area::create([
            'name' => strtoupper($request->name),
            'code' => $request->code ? strtoupper($request->code) : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('areas.index')->with('success', 'Area created successfully.');
    }

    public function edit(Area $area)
    {
        return view('areas.edit', compact('area'));
    }

    public function update(Request $request, Area $area)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:areas,name,' . $area->id,
            'code' => 'nullable|string|max:50',
        ]);

        $area->update([
            'name' => strtoupper($request->name),
            'code' => $request->code ? strtoupper($request->code) : null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('areas.index')->with('success', 'Area updated successfully.');
    }

    public function destroy(Area $area)
    {
        if ($area->mines()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete area with associated mines.']);
        }
        $area->delete();
        return redirect()->route('areas.index')->with('success', 'Area deleted.');
    }
}
