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
        $indicatorValidationService = new IndicatorValidationService();

        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();

        $constructRequenst = new ConstructRequest();

        $constructRequenst->indicatorRepository = $indicatorRepository;
        $constructRequenst->levelRepository = $levelRepository;

        $indicatorService = new IndicatorService($constructRequenst);

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

        $indicatorInsertRequenst = new IndicatorInsertRequest();

        $indicatorInsertRequenst->validity = $request->post('validity');
        $indicatorInsertRequenst->weight = $request->post('weight');
        $indicatorInsertRequenst->dummy = $request->post('dummy');
        $indicatorInsertRequenst->reducing_factor = $request->post('reducing_factor');
        $indicatorInsertRequenst->polarity = $request->post('polarity');
        $indicatorInsertRequenst->indicator = $request->post('indicator');
        $indicatorInsertRequenst->formula = $request->post('formula');
        $indicatorInsertRequenst->measure = $request->post('measure');
        $indicatorInsertRequenst->user_id = $request->header('X-User-Id');

        $insert = $indicatorService->store($indicatorInsertRequenst);

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

        $constructRequenst = new ConstructRequest();

        $constructRequenst->indicatorRepository = $indicatorRepository;

        $indicatorService = new IndicatorService($constructRequenst);

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

        $constructRequenst = new ConstructRequest();

        $constructRequenst->indicatorRepository = $indicatorRepository;

        $indicatorService = new IndicatorService($constructRequenst);

        $indicator = $indicatorService->destroy($id);
    }
}
