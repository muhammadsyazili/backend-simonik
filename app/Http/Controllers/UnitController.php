<?php

namespace App\Http\Controllers;

use App\DTO\ConstructRequest;
use App\DTO\UnitCreateRequest;
use App\DTO\UnitDestroyRequest;
use App\DTO\UnitEditRequest;
use App\DTO\UnitStoreRequest;
use App\DTO\UnitUpdateRequest;
use App\Repositories\IndicatorRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\TargetRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\UnitService;
use App\Services\UnitValidationService;

class UnitController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->unitRepository = $unitRepository;

        $unitService = new UnitService($constructRequest);

        $response = $unitService->index();

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Units",
            [
                'units' => $response->units
            ],
            null,
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $userRepository = new UserRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;

        $requestDTO = new UnitCreateRequest();

        $requestDTO->userId = $request->header('X-User-Id');

        $unitService = new UnitService($constructRequest);

        $response = $unitService->create($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Unit - Create",
            [
                'levels' => $response->levels,
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
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();
        $indicatorRepository = new IndicatorRepository();
        $targetRepository = new TargetRepository();
        $realizationRepository = new RealizationRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->targetRepository = $targetRepository;
        $constructRequest->realizationRepository = $realizationRepository;

        $unitValidationService = new UnitValidationService($constructRequest);

        $validation = $unitValidationService->storeValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new UnitStoreRequest();

        $requestDTO->name = $request->post('name');
        $requestDTO->level = $request->post('level');
        $requestDTO->parent_unit = $request->post('parent_unit');
        $requestDTO->userId = $request->header('X-User-Id');

        $unitService = new UnitService($constructRequest);

        $unitService->store($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Level Berhasil Ditambahkan",
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
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;

        $requestDTO = new UnitEditRequest();

        $requestDTO->id = $id;
        $requestDTO->userId = $request->header('X-User-Id');

        $unitService = new UnitService($constructRequest);

        $response = $unitService->edit($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Unit - Edit",
            [
                'levels' => $response->levels,
                'unit' => $response->unit,
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
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;

        $unitValidationService = new UnitValidationService($constructRequest);

        $validation = $unitValidationService->updateValidation($request, $id);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new UnitUpdateRequest();

        $requestDTO->id = $id;
        $requestDTO->name = $request->post('name');
        $requestDTO->level = $request->post('level');
        $requestDTO->parent_unit = $request->post('parent_unit');
        $requestDTO->userId = $request->header('X-User-Id');

        $unitService = new UnitService($constructRequest);

        $unitService->update($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Level Berhasil Diubah",
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
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->unitRepository = $unitRepository;

        $unitValidationService = new UnitValidationService($constructRequest);

        $validation = $unitValidationService->destroyValidation($id);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new UnitDestroyRequest();

        $requestDTO->id = $id;

        $levelService = new UnitService($constructRequest);

        $levelService->destroy($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Unit Berhasil Dihapus",
            null,
            null,
        );
    }

    /**
     * Units of level.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function units_of_level($slug)
    {
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;

        $unitService = new UnitService($constructRequest);

        $units = $unitService->units_of_level($slug);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Unit: $slug",
            $units,
            null,
        );
    }
}
