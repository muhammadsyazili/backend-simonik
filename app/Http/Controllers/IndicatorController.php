<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\DTO\ConstructRequest;
use App\DTO\IndicatorInsertOrUpdateRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\TargetRepository;
use App\Services\IndicatorService;
use App\Services\IndicatorValidationService;

class IndicatorController extends ApiController
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {

        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;

        $indicatorValidationService = new IndicatorValidationService();

        $validation = $indicatorValidationService->storeValidation($request);

        if($validation->fails()){
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $indicatorInsertOrUpdateRequest = new IndicatorInsertOrUpdateRequest();

        $indicatorInsertOrUpdateRequest->validity = $request->post('validity');
        $indicatorInsertOrUpdateRequest->weight = $request->post('weight');
        $indicatorInsertOrUpdateRequest->dummy = $request->post('dummy');
        $indicatorInsertOrUpdateRequest->reducing_factor = $request->post('reducing_factor');
        $indicatorInsertOrUpdateRequest->polarity = $request->post('polarity');
        $indicatorInsertOrUpdateRequest->indicator = $request->post('indicator');
        $indicatorInsertOrUpdateRequest->formula = $request->post('formula');
        $indicatorInsertOrUpdateRequest->measure = $request->post('measure');
        $indicatorInsertOrUpdateRequest->user_id = $request->header('X-User-Id');

        $indicatorService = new IndicatorService($constructRequest);

        $insert = $indicatorService->store($indicatorInsertOrUpdateRequest);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "KPI berhasil ditambahkan",
            $insert,
            null,
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string|int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $indicatorRepository = new IndicatorRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;

        $indicatorService = new IndicatorService($constructRequest);

        $indicator = $indicatorService->edit($id);
        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "KPI ditampilkan",
            $indicator,
            null,
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $indicatorRepository = new IndicatorRepository();
        $targetRepository = new TargetRepository();
        $realizationRepository = new RealizationRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->targetRepository = $targetRepository;
        $constructRequest->realizationRepository = $realizationRepository;

        $indicatorValidationService = new IndicatorValidationService();

        $validation = $indicatorValidationService->updateValidation($request);

        if($validation->fails()){
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $indicatorInsertOrUpdateRequest = new IndicatorInsertOrUpdateRequest();

        $indicatorInsertOrUpdateRequest->id = $id;
        $indicatorInsertOrUpdateRequest->indicator = $request->post('indicator');
        $indicatorInsertOrUpdateRequest->dummy = $request->post('dummy');
        $indicatorInsertOrUpdateRequest->reducing_factor = $request->post('reducing_factor');
        $indicatorInsertOrUpdateRequest->polarity = $request->post('polarity');
        $indicatorInsertOrUpdateRequest->formula = $request->post('formula');
        $indicatorInsertOrUpdateRequest->measure = $request->post('measure');
        $indicatorInsertOrUpdateRequest->validity = $request->post('validity');
        $indicatorInsertOrUpdateRequest->weight = $request->post('weight');

        $indicatorService = new IndicatorService($constructRequest);

        $indicatorService->update($indicatorInsertOrUpdateRequest, $id);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "KPI berhasil diubah",
            null,
            null,
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string|int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $indicatorRepository = new IndicatorRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;

        $indicatorValidationService = new IndicatorValidationService();

        $validation = $indicatorValidationService->destroyValidation($id);

        if($validation->fails()){
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $indicatorService = new IndicatorService($constructRequest);

        $indicatorService->destroy($id);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "KPI berhasil dihapus",
            null,
            null,
        );
    }
}
