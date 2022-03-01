<?php

namespace App\Services;

use App\Domains\User;
use App\Domains\User__PasswordModify;
use App\DTO\ConstructRequest;
use App\DTO\UserEditResponse;
use App\DTO\UserCreateResponse;
use App\DTO\UserDestroyRequest;
use App\DTO\UserEditRequest;
use App\DTO\UserIndexResponse;
use App\DTO\UserPasswordChangeRequest;
use App\DTO\UserPasswordResetRequest;
use App\DTO\UserStatusCheckRequest;
use App\DTO\UserStatusCheckResponse;
use App\DTO\UserUpdateRequest;
use App\DTO\UserStoreRequest;
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
    public function index(): UserIndexResponse
    {
        $response = new UserIndexResponse();

        $users = $this->userRepository->find__all__with__role_unit_level();

        $newUsers = $users->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'nip' => is_null($item->nip) ? '-' : $item->nip,
                'username' => $item->username,
                'email' => $item->email,
                'bg_color_actived' => $item->actived ? 'bg-success' : 'bg-secondary',
                'actived' => $item->actived ? 'active' : 'default',
                'unit_name' => is_null($item->unit) ? '-' : $item->unit->name,
                'role_name' => $item->role->name,
                'edit_modificable' => in_array($item->role->name, ['super-admin', 'admin', 'data-entry']) ? false : true,
                'delete_modificable' => in_array($item->role->name, ['super-admin', 'admin', 'data-entry']) ? false : true,
            ];
        });

        $response->users = $newUsers;

        return $response;
    }

    //use repo UnitRepository
    public function create(): UserCreateResponse
    {
        $response = new UserCreateResponse();

        $units = $this->unitRepository->find__all();

        $newUnits = $units->map(function ($item) {
            return [
                'slug' => $item->slug,
                'name' => $item->name,
            ];
        });

        $response->units = $newUnits;

        return $response;
    }

    //use repo UnitRepository, RoleRepository, UserRepository
    public function store(UserStoreRequest $userRequest): void
    {
        DB::transaction(function () use ($userRequest) {
            $userDomain = new User();

            $userDomain->id = (string) Str::orderedUuid();
            $userDomain->nip = $userRequest->nip;
            $userDomain->name = $userRequest->name;
            $userDomain->username = $userRequest->username;
            $userDomain->email = $userRequest->email;
            $userDomain->actived = false;
            $userDomain->password = Hash::make('1234567890');
            $userDomain->unit_id = $this->unitRepository->find__id__by__slug($userRequest->unit);
            $userDomain->role_id = $this->roleRepository->find__id__by__name('employee');

            $this->userRepository->save($userDomain);
        });
    }

    //use repo UnitRepository, UserRepository
    public function edit(UserEditRequest $userRequest): UserEditResponse
    {
        $response = new UserEditResponse();

        $user = $this->userRepository->find__by__id($userRequest->id);

        $user = [
            'id' => $user->id,
            'name' => $user->name,
            'nip' => $user->nip,
            'username' => $user->username,
            'email' => $user->email,
            'unit_id' => $user->unit_id,
        ];

        $response->user = $user;

        $units = $this->unitRepository->find__all();

        $newUnits = $units->map(function ($item) {
            return [
                'id' => $item->id,
                'slug' => $item->slug,
                'name' => $item->name,
            ];
        });

        $response->units = $newUnits;

        return $response;
    }

    //use repo UserRepository
    public function update(UserUpdateRequest $user): void
    {
        DB::transaction(function () use ($user) {
            $userDomain = new User();

            $userDomain->id = $user->id;
            $userDomain->nip = $user->nip;
            $userDomain->name = $user->name;
            $userDomain->username = $user->username;
            $userDomain->email = $user->email;
            $userDomain->unit_id = $this->unitRepository->find__id__by__slug($user->unit);

            $this->userRepository->update__by__id($userDomain);
        });
    }

    //use repo UserRepository
    public function destroy(UserDestroyRequest $userRequest): void
    {
        DB::transaction(function () use ($userRequest) {
            $this->userRepository->delete__by__id($userRequest->id);
        });
    }

    //use repo UserRepository
    public function password_reset(UserPasswordResetRequest $userRequest): void
    {
        DB::transaction(function () use ($userRequest) {
            $userDomain = new User__PasswordModify();

            $userDomain->id = $userRequest->id;
            $userDomain->password = Hash::make('1234567890');

            $this->userRepository->update__password__by__id($userDomain, false);
        });
    }

    //use repo UserRepository
    public function password_change(UserPasswordChangeRequest $userRequest): void
    {
        DB::transaction(function () use ($userRequest) {
            $userDomain = new User__PasswordModify();

            $userDomain->id = $userRequest->id;
            $userDomain->password = Hash::make($userRequest->password);

            $this->userRepository->update__password__by__id($userDomain, true);
        });
    }

    //use repo UserRepository
    public function active_check(UserStatusCheckRequest $userRequest): UserStatusCheckResponse
    {
        $response = new UserStatusCheckResponse();

        $response->actived = $this->userRepository->find__actived__by__id($userRequest->id);

        return $response;
    }
}
