<?php

namespace App\Http\Controllers\Extends\Indicator;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\ApiController;
use App\DTO\ConstructRequest;
use App\DTO\IndicatorReferenceEditRequest;
use App\DTO\IndicatorReferenceUpdateRequest;
use App\DTO\IndicatorReferenceStoreRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Services\IndicatorReferenceService;
use App\Services\IndicatorReferenceValidationService;

class IndicatorReferenceController extends ApiController
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        $indicatorRepository = new IndicatorRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;

        $indicatorReferenceService = new IndicatorReferenceService($constructRequest);

        $response = $indicatorReferenceService->create();

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas Kerja KPI Referensi",
            [
                'indicators' => $response->indicators,
                'preferences' => $response->preferences,
            ],
            null,
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $indicatorRepository = new IndicatorRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;

        $indicatorReferenceValidationService = new IndicatorReferenceValidationService($constructRequest);

        $validation = $indicatorReferenceValidationService->storeValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new IndicatorReferenceStoreRequest();

        $requestDTO->indicators = $request->post('indicators');
        $requestDTO->preferences = $request->post('preferences');
        $requestDTO->userId = $request->header('X-User-Id');

        $indicatorReferenceService = new IndicatorReferenceService($constructRequest);

        $indicatorReferenceService->store($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas Kerja KPI (Level: SUPER-MASTER) Berhasil Direferensikan",
            null,
            null,
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request)
    {
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;

        $indicatorReferenceValidationService = new IndicatorReferenceValidationService();

        $validation = $indicatorReferenceValidationService->editValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new IndicatorReferenceEditRequest();

        $requestDTO->level = $request->query('level');
        $requestDTO->unit = $request->query('unit');
        $requestDTO->year = $request->query('tahun');

        $indicatorReferenceService = new IndicatorReferenceService($constructRequest);

        $response = $indicatorReferenceService->edit($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas Kerja KPI Referensi - Edit",
            [
                'indicators' => $response->indicators,
                'preferences' => $response->preferences,
            ],
            null,
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;

        $indicatorReferenceValidationService = new IndicatorReferenceValidationService($constructRequest);

        $validation = $indicatorReferenceValidationService->updateValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new IndicatorReferenceUpdateRequest();

        $requestDTO->indicators = $request->post('indicators');
        $requestDTO->preferences = $request->post('preferences');
        $requestDTO->level = $request->post('level');
        $requestDTO->unit = $request->post('unit');
        $requestDTO->year = $request->post('tahun');
        $requestDTO->userId = $request->header('X-User-Id');

        $IndicatorReferenceService = new IndicatorReferenceService($constructRequest);

        $IndicatorReferenceService->update($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            $requestDTO->level === 'super-master' ? sprintf("Kertas Kerja KPI (Level: %s) Berhasil Direferensikan", strtoupper($requestDTO->level)) : sprintf("Kertas Kerja KPI (Level: %s) (Unit: %s) (Tahun: %s) Berhasil Direferensikan", strtoupper($requestDTO->level), strtoupper($requestDTO->unit), strtoupper($requestDTO->year)),
            null,
            null,
        );
    }
}
