<?php

namespace App\Http\Controllers;

use App\DTO\ConstructRequest;
use App\DTO\UnitStoreOrUpdateRequest;
use App\Repositories\IndicatorRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
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

        $units = $unitService->index();

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Units",
            [
                'units' => $units
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

        $unitService = new UnitService($constructRequest);

        $userId = $request->header('X-User-Id');

        $response = $unitService->create($userId);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Unit - Create",
            [
                'levels' => $response->levels,
                'units' => $response->units
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

        $UnitStoreOrUpdateRequest = new UnitStoreOrUpdateRequest();

        $UnitStoreOrUpdateRequest->name = $request->post('name');
        $UnitStoreOrUpdateRequest->level = $request->post('level');
        $UnitStoreOrUpdateRequest->parent_unit = $request->post('parent_unit');
        $UnitStoreOrUpdateRequest->userId = $request->header('X-User-Id');

        $unitService = new UnitService($constructRequest);

        $unitService->store($UnitStoreOrUpdateRequest);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Level berhasil ditambahkan",
            null,
            null,
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Display a listing of units by level the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function unitsOfLevel(Request $request, $slug)
    {
        //logging
        // $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        // $output->writeln(sprintf('id: %s', $request->header('X-User-Id')));

        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;

        $unitService = new UnitService($constructRequest);

        $units = $unitService->unitsOfLevel($slug);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Unit: $slug",
            $units,
            null,
        );
    }
}
