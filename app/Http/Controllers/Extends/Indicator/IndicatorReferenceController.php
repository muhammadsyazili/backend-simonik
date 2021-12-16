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
     * @return \Illuminate\Http\Response
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
            "Indicators referencing showed",
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
     * @return \Illuminate\Http\Response
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

        $indicatorReferenceService->store($request->post('indicators'), $request->post('preferences'));

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indicators referenced successfully",
            null,
            null,
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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

        $indicatorReferenceValidationService = new IndicatorReferenceValidationService($constructRequest);

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

        $indicators = $indicatorReferenceService->edit($request->query('level'), $request->query('unit'), $request->query('tahun'));

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indicators referencing showed",
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
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

        $IndicatorReferenceService->update($request->post('indicators'), $request->post('preferences'), $request->post('level'), $request->post('unit'), $request->post('tahun'));

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indicators referenced successfully",
            null,
            null,
        );
    }
}
