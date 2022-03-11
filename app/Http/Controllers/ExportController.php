<?php

namespace App\Http\Controllers;

use App\DTO\ConstructRequest;
use App\DTO\ExportIndexRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\ExportService;
use App\Services\ExportValidationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends ApiController
{
    public function export(Request $request)
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

        $exportValidationService = new ExportValidationService($constructRequest);

        $validation = $exportValidationService->exportValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new ExportIndexRequest();

        $requestDTO->level = $request->query('level');
        $requestDTO->unit = $request->query('unit');
        $requestDTO->year = $request->query('tahun');

        $exportService = new ExportService($constructRequest);

        $response = $exportService->export($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Export",
            [
                'indicators' => $response->indicators,
            ],
            null,
        );
    }
}
