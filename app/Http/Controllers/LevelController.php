<?php

namespace App\Http\Controllers;

use App\DTO\ConstructRequest;
use App\DTO\LevelDestroyRequest;
use App\DTO\LevelEditRequest;
use App\DTO\LevelUpdateRequest;
use App\DTO\LevelStoreRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
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

        $response = $levelService->index();

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Levels",
            [
                'levels' => $response->levels,
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

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new LevelStoreRequest();

        $requestDTO->name = $request->post('name');
        $requestDTO->parentLevel = $request->post('parent_level');

        $levelService = new LevelService($constructRequest);

        $levelService->store($requestDTO);

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
    public function edit($id)
    {
        $levelRepository = new LevelRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->levelRepository = $levelRepository;

        $requestDTO = new LevelEditRequest();

        $requestDTO->id = $id;

        $levelService = new LevelService($constructRequest);

        $response = $levelService->edit($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Level - Edit",
            [
                'levels' => $response->levels,
                'level' => $response->level,
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

        $levelValidationService = new LevelValidationService($constructRequest);

        $validation = $levelValidationService->updateValidation($request, $id);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new LevelUpdateRequest();

        $requestDTO->id = $id;
        $requestDTO->name = $request->post('name');
        $requestDTO->parentLevel = $request->post('parent_level');

        $levelService = new LevelService($constructRequest);

        $levelService->update($requestDTO);

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
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;

        $levelValidationService = new LevelValidationService($constructRequest);

        $validation = $levelValidationService->destroyValidation($id);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new LevelDestroyRequest();

        $requestDTO->id = $id;

        $levelService = new LevelService($constructRequest);

        $levelService->destroy($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Level Berhasil Dihapus",
            null,
            null,
        );
    }

    /**
     * get levels by user id.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_levels_by_userId(Request $request, $id)
    {
        $levelRepository = new LevelRepository();
        $userRepository = new UserRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->levelRepository = $levelRepository;

        $levelService = new LevelService($constructRequest);

        $levels = $levelService->get_levels_by_userId($id, $request->query('with-super-master') === 'true' ? true : false);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Levels of '$id'",
            $levels,
            null,
        );
    }

    /**
     * public levels.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function public_levels()
    {
        $levelRepository = new LevelRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->levelRepository = $levelRepository;

        $levelService = new LevelService($constructRequest);

        $levels = $levelService->public_levels();

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Levels",
            $levels,
            null,
        );
    }

    /**
     * get parents by level slug.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_parents_by_levelSlug($slug)
    {
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->levelRepository = $levelRepository;

        $levelService = new LevelService($constructRequest);

        $levels = $levelService->get_parents_by_levelSlug($slug);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Parents",
            $levels,
            null,
        );
    }

    /**
     * get categories.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_categories()
    {
        $levelRepository = new LevelRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->levelRepository = $levelRepository;

        $levelService = new LevelService($constructRequest);

        $categories = $levelService->get_categories();

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Categories of level",
            $categories,
            null,
        );
    }
}
