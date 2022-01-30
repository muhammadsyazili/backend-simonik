<?php

namespace App\Http\Controllers\Extends\Indicator;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Symfony\Component\HttpFoundation\Response;
use App\DTO\ConstructRequest;
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

        $create = $indicatorReferenceService->create();

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas kerja KPI referensi ditampilkan !",
            [
                'indicators' => $create->indicators,
                'preferences' => $create->preferences,
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

        if($validation->fails()){
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $indicatorReferenceService = new IndicatorReferenceService($constructRequest);

        $indicators = $request->post('indicators');
        $preferences = $request->post('preferences');

        $indicatorReferenceService->store($indicators, $preferences);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas kerja KPI (Level: SUPER-MASTER) berhasil direferensikan !",
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

        $indicators = $indicatorReferenceService->edit($level, $unit, $tahun);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas kerja KPI referensi ditampilkan !",
            [
                'indicators' => $indicators,
                'preferences' => $indicators,
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

        if($validation->fails()){
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $IndicatorReferenceService = new IndicatorReferenceService($constructRequest);

        $indicators = $request->post('indicators');
        $preferences = $request->post('preferences');
        $level = $request->post('level');
        $unit = $request->post('unit');
        $tahun = $request->post('tahun');

        $IndicatorReferenceService->update($indicators, $preferences, $level, $unit, $tahun);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            $level === 'super-master' ? sprintf("Kertas kerja KPI (Level: %s) berhasil direferensikan", strtoupper($level)) : sprintf("Kertas kerja KPI (Level: %s) (Unit: %s) (Tahun: %s) berhasil direferensikan", strtoupper($level), strtoupper($unit), strtoupper($tahun)),
            null,
            null,
        );
    }
}
