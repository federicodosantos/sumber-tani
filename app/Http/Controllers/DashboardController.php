<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        try {
            $user = Auth::user();
            Log::debug('Dashboard user role', ['id' => $user->id ?? null, 'role' => $user->role ?? null]);

            $data = $this->dashboardService->getSummary($user);

            return view('dashboard.index', $data + ['user' => $user]);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['general' => 'Terjadi kesalahan pada sistem: ' . $e->getMessage()]);
        }
    }
}
