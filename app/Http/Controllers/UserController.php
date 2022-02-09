<?php

namespace App\Http\Controllers;

use App\DTO\ConstructRequest;
use App\DTO\UserInsertOrUpdateRequest;
use App\Repositories\RoleRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Services\UserValidationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $userRepository = new UserRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;

        $userService = new UserService($constructRequest);

        $users = $userService->index();

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Users",
            [
                'users' => $users
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
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->unitRepository = $unitRepository;

        $userService = new UserService($constructRequest);

        $response = $userService->create();

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "User - Create",
            [
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
        $unitRepository = new UnitRepository();
        $userRepository = new UserRepository();
        $roleRepository = new RoleRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->userRepository = $userRepository;
        $constructRequest->roleRepository = $roleRepository;

        $userValidationService = new UserValidationService($constructRequest);

        $validation = $userValidationService->storeValidation($request);

        if($validation->fails()){
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $userInsertOrUpdateRequest = new UserInsertOrUpdateRequest();

        $userInsertOrUpdateRequest->name = $request->post('name');
        $userInsertOrUpdateRequest->nip = $request->post('nip');
        $userInsertOrUpdateRequest->username = $request->post('username');
        $userInsertOrUpdateRequest->email = $request->post('email');
        $userInsertOrUpdateRequest->unit = $request->post('unit');

        $userService = new UserService($constructRequest);

        $userService->store($userInsertOrUpdateRequest);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "User berhasil ditambahkan",
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
        $userRepository = new UserRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->unitRepository = $unitRepository;

        $userService = new UserService($constructRequest);

        $response = $userService->edit($id);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "User - Edit",
            [
                'user' => $response->user,
                'units' => $response->units,
            ],
            null,
        );
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
        $unitRepository = new UnitRepository();
        $userRepository = new UserRepository();
        $roleRepository = new RoleRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->userRepository = $userRepository;
        $constructRequest->roleRepository = $roleRepository;

        $userValidationService = new UserValidationService($constructRequest);

        $validation = $userValidationService->updateValidation($request, $id);

        if($validation->fails()){
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $userInsertOrUpdateRequest = new UserInsertOrUpdateRequest();

        $userInsertOrUpdateRequest->id = $id;
        $userInsertOrUpdateRequest->name = $request->post('name');
        $userInsertOrUpdateRequest->nip = $request->post('nip');
        $userInsertOrUpdateRequest->username = $request->post('username');
        $userInsertOrUpdateRequest->email = $request->post('email');
        $userInsertOrUpdateRequest->unit = $request->post('unit');

        $userService = new UserService($constructRequest);

        $userService->update($userInsertOrUpdateRequest);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "User berhasil diubah",
            null,
            null,
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $userRepository = new UserRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;

        $userValidationService = new UserValidationService($constructRequest);

        $validation = $userValidationService->destroyValidation($id);

        if($validation->fails()){
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $userService = new UserService($constructRequest);

        $userService->destroy($id);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "User berhasil dihapus",
            null,
            null,
        );
    }
}
