<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Support\DatePeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends PanelController
{
    public function admin(DashboardService $dashboard): View
    {
        return view('admin.dashboard', $dashboard->admin());
    }

    public function peternak(Request $request, DashboardService $dashboard): View
    {
        $period = DatePeriod::fromRequest($request, '6m');
        $cattleId = $request->integer('cattle_id') ?: null;

        return view('peternak.dashboard', $dashboard->farmer($request->user(), $period, $cattleId));
    }
}
