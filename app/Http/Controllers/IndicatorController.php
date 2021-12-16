<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\DTO\ConstructRequest;
use App\DTO\IndicatorInsertRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Services\IndicatorService;
use App\Services\IndicatorValidationService;

class IndicatorController extends ApiController
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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

        $indicatorInsertRequest = new IndicatorInsertRequest();

        $indicatorInsertRequest->validity = $request->post('validity');
        $indicatorInsertRequest->weight = $request->post('weight');
        $indicatorInsertRequest->dummy = $request->post('dummy');
        $indicatorInsertRequest->reducing_factor = $request->post('reducing_factor');
        $indicatorInsertRequest->polarity = $request->post('polarity');
        $indicatorInsertRequest->indicator = $request->post('indicator');
        $indicatorInsertRequest->formula = $request->post('formula');
        $indicatorInsertRequest->measure = $request->post('measure');
        $indicatorInsertRequest->user_id = $request->header('X-User-Id');

        $indicatorService = new IndicatorService($constructRequest);

        $insert = $indicatorService->store($indicatorInsertRequest);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indicator creating successfully",
            $insert,
            null,
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $indicatorRepository = new IndicatorRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;

        $indicatorService = new IndicatorService($constructRequest);

        $indicator = $indicatorService->show($id);
        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indicator creating successfully",
            $indicator,
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
    public function update(Request $request, $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
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

        $indicator = $indicatorService->destroy($id);
    }
}
