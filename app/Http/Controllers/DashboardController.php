<?php

namespace App\Http\Controllers;

use App\DTO\ConstructRequest;
use App\DTO\DashboardDashboardRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends ApiController
{
    public function dashboard(Request $request)
    {
        $userRepository = new UserRepository();
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;

        $requestDTO = new DashboardDashboardRequest();

        $requestDTO->level = $request->query('level');
        $requestDTO->unit = $request->query('unit');
        $requestDTO->year = (int) $request->query('tahun');
        $requestDTO->month = $request->query('bulan');

        $dashboardService = new DashboardService($constructRequest);

        $response = $dashboardService->dashboard($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Dashboard",
            [
                'indicators' => $response->indicators,
            ],
            null,
        );
    }
}