<?php

namespace App\Providers;

use App\Services\GoodsReceiptService;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.tailwind');
        Paginator::defaultSimpleView('vendor.pagination.simple-tailwind');
        Carbon::setLocale('id');

        // Badge "barang belum datang" di sidebar. Hanya dihitung saat sidebar
        // benar-benar dirender, bukan di setiap request.
        View::composer('components.partials.sidebar', function ($view) {
            $view->with(
                'pendingReceiptCount',
                app(GoodsReceiptService::class)->outstandingCount()
            );
        });
    }
}
