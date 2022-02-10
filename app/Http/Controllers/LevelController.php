<?php

namespace App\Http\Controllers;

use App\DTO\ConstructRequest;
use App\DTO\LevelInsertOrUpdateRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\LevelRepository;
use App\Repositories\UserRepository;
use App\Services\LevelService;
use App\Services\LevelValidationService;

class LevelController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $levelRepository = new LevelRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->levelRepository = $levelRepository;

        $levelService = new LevelService($constructRequest);

        $levels = $levelService->index();

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Levels",
            [
                'levels' => $levels
            ],
            null,
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        $levelRepository = new LevelRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->levelRepository = $levelRepository;

        $levelService = new LevelService($constructRequest);

        $response = $levelService->create();

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Level - Create",
            [
                'levels' => $response->levels
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

        $constructRequest = new ConstructRequest();

        $constructRequest->levelRepository = $levelRepository;

        $levelValidationService = new LevelValidationService($constructRequest);

        $validation = $levelValidationService->storeValidation($request);

        if($validation->fails()){
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $levelInsertOrUpdateRequest = new LevelInsertOrUpdateRequest();

        $levelInsertOrUpdateRequest->name = $request->post('name');
        $levelInsertOrUpdateRequest->parent_level = $request->post('parent_level');

        $levelService = new LevelService($constructRequest);

        $levelService->store($levelInsertOrUpdateRequest);

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
     * Display a listing of levels by user the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function levelsOfUser(Request $request, $id)
    {
        $levelRepository = new LevelRepository();
        $userRepository = new UserRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->levelRepository = $levelRepository;

        $levelService = new LevelService($constructRequest);

        $levels = $levelService->levelsOfUser($id, $request->query('with-super-master') === 'true' ? true : false);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Levels of '$id'",
            $levels,
            null,
        );
    }
}
