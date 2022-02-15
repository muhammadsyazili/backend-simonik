<?php

namespace App\Services;

use App\Domains\User;
use App\DTO\ConstructRequest;
use App\DTO\UserCreateOrEditResponse;
use App\DTO\UserStoreOrUpdateRequest;
use App\Repositories\RoleRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserService
{
    private ?UserRepository $userRepository;
    private ?UnitRepository $unitRepository;
    private ?RoleRepository $roleRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->userRepository = $constructRequest->userRepository;
        $this->unitRepository = $constructRequest->unitRepository;
        $this->roleRepository = $constructRequest->roleRepository;
    }

    //use repo UserRepository
    public function index()
    {
        return $this->userRepository->find__all__with__role_unit_level();
    }

    //use repo UnitRepository
    public function create(): UserCreateOrEditResponse
    {
        $response = new UserCreateOrEditResponse();

        $response->units = $this->unitRepository->find__all();

        return $response;
    }

    //use repo UnitRepository, RoleRepository, UserRepository
    public function store(UserStoreOrUpdateRequest $user): void
    {
        DB::transaction(function () use ($user) {
            $userDomain = new User();

            $userDomain->id = (string) Str::orderedUuid();
            $userDomain->nip = $user->nip;
            $userDomain->name = $user->name;
            $userDomain->username = $user->username;
            $userDomain->email = $user->email;
            $userDomain->actived = false;
            $userDomain->password = Hash::make('1234567890');
            $userDomain->unit_id = $this->unitRepository->find__id__by__slug($user->unit);
            $userDomain->role_id = $this->roleRepository->find__id__by__name('employee');

            $this->userRepository->save($userDomain);
        });
    }

    //use repo UnitRepository, UserRepository
    public function edit(string|int $id): UserCreateOrEditResponse
    {
        $response = new UserCreateOrEditResponse();

        $response->user = $this->userRepository->find__by__id($id);
        $response->units = $this->unitRepository->find__all();

        return $response;
    }

    //use repo UserRepository
    public function update(UserStoreOrUpdateRequest $user): void
    {
        DB::transaction(function () use ($user) {
            $userDomain = new User();

            $userDomain->nip = $user->nip;
            $userDomain->name = $user->name;
            $userDomain->username = $user->username;
            $userDomain->email = $user->email;
            $userDomain->unit_id = $this->unitRepository->find__id__by__slug($user->unit);

            $this->userRepository->update__by__id($userDomain, $user->id);
        });
    }

    //use repo UserRepository
    public function destroy(string|int $id): void
    {
        DB::transaction(function () use ($id) {
            $this->userRepository->delete__by__id($id);
        });
    }
}
