<?php

namespace App\Providers;

use App\Models\CostsheetData;
use App\Observers\CostsheetDataObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        CostsheetData::observe(CostsheetDataObserver::class);
        Paginator::useBootstrapFive();
    }
}
