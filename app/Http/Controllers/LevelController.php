<?php

namespace App\Http\Controllers;

use App\DTO\ConstructRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\LevelRepository;
use App\Repositories\UserRepository;
use App\Services\LevelService;

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
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        //
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
