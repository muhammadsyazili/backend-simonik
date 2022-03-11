<?php

namespace App\Http\Controllers;

use App\DTO\ConstructRequest;
use App\DTO\MonitoringMonitoringRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\MonitoringService;
use App\Services\MonitoringValidationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MonitoringController extends ApiController
{
    public function monitoring(Request $request)
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

        if ($request->query('auth') === '1') {
            $monitoringValidationService = new MonitoringValidationService($constructRequest);

            $validation = $monitoringValidationService->monitoringValidation($request);

            if ($validation->fails()) {
                return $this->APIResponse(
                    false,
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                    null,
                    $validation->errors(),
                );
            }
        }

        $requestDTO = new MonitoringMonitoringRequest();

        $requestDTO->level = $request->query('level');
        $requestDTO->unit = $request->query('unit');
        $requestDTO->year = $request->query('tahun');
        $requestDTO->month = $request->query('bulan');

        $monitoringService = new MonitoringService($constructRequest);

        $response = $monitoringService->monitoring($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Monitoring",
            [
                'indicators' => $response->indicators,
            ],
            null,
        );
    }

    public function monitoring_by_id($id, $prefix, $month)
    {
        $indicatorRepository = new IndicatorRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;

        $monitoringValidationService = new MonitoringValidationService($constructRequest);

        $validation = $monitoringValidationService->monitoringByIdValidation($id);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $monitoringService = new MonitoringService($constructRequest);

        $indicator = $monitoringService->monitoring_by_id($id, $month, $prefix);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Monitoring",
            [
                'indicator' => $indicator,
            ],
            null,
        );
    }
}
