<?php

namespace App\Http\Controllers\Extends\Indicator;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Symfony\Component\HttpFoundation\Response;
use App\DTO\ConstructRequest;
use App\DTO\IndicatorReferenceStoreOrUpdateRequest;
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
            "Kertas kerja KPI referensi - Create",
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

        $requestDTO = new IndicatorReferenceStoreOrUpdateRequest();

        $requestDTO->indicators = $request->post('indicators');
        $requestDTO->preferences = $request->post('preferences');
        $requestDTO->userId = $request->header('X-User-Id');

        $indicatorReferenceService = new IndicatorReferenceService($constructRequest);

        $indicatorReferenceService->store($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas kerja KPI (Level: SUPER-MASTER) berhasil direferensikan",
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

        $indicatorReferenceService = new IndicatorReferenceService($constructRequest);

        $level = $request->query('level');
        $unit = $request->query('unit');
        $tahun = $request->query('tahun');

        $response = $indicatorReferenceService->edit($level, $unit, $tahun);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas kerja KPI referensi - Edit",
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

        $requestDTO = new IndicatorReferenceStoreOrUpdateRequest();

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
            $requestDTO->level === 'super-master' ? sprintf("Kertas kerja KPI (Level: %s) berhasil direferensikan", strtoupper($requestDTO->level)) : sprintf("Kertas kerja KPI (Level: %s) (Unit: %s) (Tahun: %s) berhasil direferensikan", strtoupper($requestDTO->level), strtoupper($requestDTO->unit), strtoupper($requestDTO->year)),
            null,
            null,
        );
    }
}
