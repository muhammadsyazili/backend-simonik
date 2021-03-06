<?php

namespace App\Http\Controllers;

use App\DTO\ConstructRequest;
use App\DTO\UserDestroyRequest;
use App\DTO\UserEditRequest;
use App\DTO\UserPasswordChangeRequest;
use App\DTO\UserPasswordResetRequest;
use App\DTO\UserStatusCheckRequest;
use App\DTO\UserUpdateRequest;
use App\DTO\UserStoreRequest;
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

        $response = $userService->index();

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Users",
            [
                'users' => $response->users,
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
            "User Create",
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

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new UserStoreRequest();

        $requestDTO->name = $request->post('nama');
        $requestDTO->nip = $request->post('nip');
        $requestDTO->username = $request->post('username');
        $requestDTO->email = $request->post('email');
        $requestDTO->unit = $request->post('unit_kerja');

        $userService = new UserService($constructRequest);

        $userService->store($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "User Created!",
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
        $userRepository = new UserRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->unitRepository = $unitRepository;

        $requestDTO = new UserEditRequest();

        $requestDTO->id = $id;

        $userService = new UserService($constructRequest);

        $response = $userService->edit($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "User Edit",
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
     * @param  string|int  $id
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

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $UserUpdateRequest = new UserUpdateRequest();

        $UserUpdateRequest->id = $id;
        $UserUpdateRequest->name = $request->post('nama');
        $UserUpdateRequest->nip = $request->post('nip');
        $UserUpdateRequest->username = $request->post('username');
        $UserUpdateRequest->email = $request->post('email');
        $UserUpdateRequest->unit = $request->post('unit_kerja');

        $userService = new UserService($constructRequest);

        $userService->update($UserUpdateRequest);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "User Updated!",
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
        $userRepository = new UserRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;

        $userValidationService = new UserValidationService($constructRequest);

        $validation = $userValidationService->destroyValidation($id);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new UserDestroyRequest();

        $requestDTO->id = $id;

        $userService = new UserService($constructRequest);

        $userService->destroy($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "User Deleted!",
            null,
            null,
        );
    }

    /**
     * Password reset.
     *
     * @param  string|int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function password_reset($id)
    {
        $userRepository = new UserRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;

        $requestDTO = new UserPasswordResetRequest();

        $requestDTO->id = $id;

        $userService = new UserService($constructRequest);

        $userService->password_reset($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "User Password Reseted!",
            null,
            null,
        );
    }

    /**
     * Password reset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function password_change(Request $request, $id)
    {
        $userRepository = new UserRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;

        $requestDTO = new UserPasswordChangeRequest();

        $requestDTO->id = $id;
        $requestDTO->password = $request->post('password');

        $userService = new UserService($constructRequest);

        $userService->password_change($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "User Password Changed!",
            null,
            null,
        );
    }

    /**
     * Active check.
     *
     * @param  string|int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function active_check($id)
    {
        $userRepository = new UserRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;

        $requestDTO = new UserStatusCheckRequest();

        $requestDTO->id = $id;

        $userService = new UserService($constructRequest);

        $response = $userService->active_check($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "User Active Check",
            [
                'user' => ['actived' => $response->actived],
            ],
            null,
        );
    }
}
