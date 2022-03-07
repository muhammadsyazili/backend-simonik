<?php

namespace App\Http\Controllers;

use App\DTO\AnalyticIndexRequest;
use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\AnalyticService;
use App\Services\AnalyticValidationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnalyticController extends ApiController
{
    public function analytic(Request $request)
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

        $analyticValidationService = new AnalyticValidationService($constructRequest);

        $validation = $analyticValidationService->analyticValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new AnalyticIndexRequest();

        $requestDTO->level = $request->query('level');
        $requestDTO->unit = $request->query('unit');
        $requestDTO->year = $request->query('tahun');
        $requestDTO->month = $request->query('bulan');
        $requestDTO->userId = $request->header('X-User-Id');

        $analyticService = new AnalyticService($constructRequest);

        $response = $analyticService->analytic($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Analytic",
            [
                'indicators' => $response->indicators,
            ],
            null,
        );
    }

    public function analytic_by_id(Request $request, $id, $prefix, $month)
    {
        $userRepository = new UserRepository();
        $indicatorRepository = new IndicatorRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;

        $analyticValidationService = new AnalyticValidationService($constructRequest);

        $validation = $analyticValidationService->analyticByIdValidation($request, $id);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $analyticService = new AnalyticService($constructRequest);

        $indicator = $analyticService->analytic_by_id($id, $month, $prefix);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Analytic",
            [
                'indicator' => $indicator,
            ],
            null,
        );
    }
}
