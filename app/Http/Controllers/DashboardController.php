<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\CostsheetData;
use App\Models\Mine;
use App\Models\UploadedFile;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAreas = Area::where('is_active', true)->count();
        $totalMines = Mine::where('is_active', true)->count();
        $totalUploads = UploadedFile::count();
        $recentUploads = UploadedFile::with(['area', 'uploader'])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact('totalAreas', 'totalMines', 'totalUploads', 'recentUploads'));
    }
}
