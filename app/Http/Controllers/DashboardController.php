<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $data = $this->dashboardService->getSummary($user);

            return view('dashboard.index', $data);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['general' => 'Terjadi kesalahan pada sistem: ' . $e->getMessage()]);
        }
    }
}
