<?php

namespace App\Http\Controllers;

use App\DTO\ConstructRequest;
use App\DTO\ExportingExportingRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\ExportingService;
use App\Services\ExportingValidationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportingController extends ApiController
{
    public function exporting(Request $request)
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

        $exportingValidationService = new ExportingValidationService($constructRequest);

        $validation = $exportingValidationService->exportValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new ExportingExportingRequest();

        $requestDTO->level = $request->query('level');
        $requestDTO->unit = $request->query('unit');
        $requestDTO->year = $request->query('tahun');
        $requestDTO->month = $request->query('bulan');

        $exportingService = new ExportingService($constructRequest);

        $response = $exportingService->exporting($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Exporting",
            [
                'indicators' => $response->indicators,
            ],
            null,
        );
    }
}
