<?php

namespace App\Http\Controllers;

use App\DTO\ConstructRequest;
use App\DTO\RangkingRangkingRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Services\RangkingService;
use App\Services\RangkingValidationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RangkingController extends ApiController
{
    public function rangking(Request $request)
    {
        $levelRepository = new LevelRepository();
        $indicatorRepository = new IndicatorRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;

        $rangkingValidationService = new RangkingValidationService();

        $validation = $rangkingValidationService->rangkingValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new RangkingRangkingRequest();

        $requestDTO->levelCategory = $request->query('kategori_level');
        $requestDTO->year = (int) $request->query('tahun');
        $requestDTO->month = $request->query('bulan');

        $rangkingService = new RangkingService($constructRequest);

        $response = $rangkingService->rangking($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Monitoring",
            [
                'units' => $response->units,
            ],
            null,
        );
    }
}
