<?php

namespace App\Http\Controllers;

use App\DTO\ComparingComparingRequest;
use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Services\ComparingService;
use App\Services\ComparingValidationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ComparingController extends ApiController
{
    public function comparing(Request $request)
    {
        $indicatorRepository = new IndicatorRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;

        $comparingValidationService = new ComparingValidationService();

        $validation = $comparingValidationService->comparingValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new ComparingComparingRequest();

        $requestDTO->idLeft = $request->query('id_kiri');
        $requestDTO->monthLeft = $request->query('bulan_kiri');

        $requestDTO->idRight = $request->query('id_kanan');
        $requestDTO->monthRight = $request->query('bulan_kanan');

        $comparingService = new ComparingService($constructRequest);

        $response = $comparingService->comparing($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Camparing",
            [
                'indicators' => $response->indicators,
            ],
            null,
        );
    }
}
