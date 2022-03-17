<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\DTO\ConstructRequest;
use App\DTO\IndicatorDestroyRequest;
use App\DTO\IndicatorEditRequest;
use App\DTO\IndicatorUpdateRequest;
use App\DTO\IndicatorStoreRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\TargetRepository;
use App\Repositories\UserRepository;
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

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new IndicatorStoreRequest();

        $requestDTO->validity = $request->post('validity');
        $requestDTO->weight = $request->post('weight');
        $requestDTO->dummy = $request->post('dummy');
        $requestDTO->type = $request->post('type');
        $requestDTO->reducing_factor = $request->post('reducing_factor');
        $requestDTO->polarity = $request->post('polarity');
        $requestDTO->indicator = $request->post('indicator');
        $requestDTO->formula = $request->post('formula');
        $requestDTO->measure = $request->post('measure');
        $requestDTO->user_id = $request->header('X-User-Id');

        $indicatorService = new IndicatorService($constructRequest);

        $indicatorService->store($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indikator Berhasil Ditambahkan",
            null,
            null,
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string|int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request, $id)
    {
        $userRepository = new UserRepository();
        $indicatorRepository = new IndicatorRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;

        $indicatorValidationService = new IndicatorValidationService($constructRequest);

        $userId = $request->header('X-User-Id');

        $validation = $indicatorValidationService->editValidation($userId, $id);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new IndicatorEditRequest();

        $requestDTO->id = $id;

        $indicatorService = new IndicatorService($constructRequest);

        $response = $indicatorService->edit($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indikator",
            [
                'indicator' => $response->indicator,
            ],
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
        $userRepository = new UserRepository();
        $indicatorRepository = new IndicatorRepository();
        $targetRepository = new TargetRepository();
        $realizationRepository = new RealizationRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->targetRepository = $targetRepository;
        $constructRequest->realizationRepository = $realizationRepository;

        $indicatorValidationService = new IndicatorValidationService($constructRequest);

        $validation = $indicatorValidationService->updateValidation($request, $id);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new IndicatorUpdateRequest();

        $requestDTO->id = $id;
        $requestDTO->indicator = $request->post('indicator');
        $requestDTO->dummy = $request->post('dummy');
        $requestDTO->type = $request->post('type');
        $requestDTO->reducing_factor = $request->post('reducing_factor');
        $requestDTO->polarity = $request->post('polarity');
        $requestDTO->formula = $request->post('formula');
        $requestDTO->measure = $request->post('measure');
        $requestDTO->validity = $request->post('validity');
        $requestDTO->weight = $request->post('weight');
        $requestDTO->user_id = $request->header('X-User-Id');

        $indicatorService = new IndicatorService($constructRequest);

        $indicatorService->update($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indikator Berhasil Diubah",
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

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new IndicatorDestroyRequest();

        $requestDTO->id = $id;

        $indicatorService = new IndicatorService($constructRequest);

        $indicatorService->destroy($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indikator Berhasil Dihapus",
            null,
            null,
        );
    }
}
