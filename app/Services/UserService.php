<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\UserCreateResponse;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;

class UserService {
    private ?UserRepository $userRepository;
    private ?UnitRepository $unitRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->userRepository = $constructRequest->userRepository;
        $this->unitRepository = $constructRequest->unitRepository;
    }

    //use repo UserRepository
    public function index()
    {
        return $this->userRepository->find__all__with__role_unit_level();
    }

    //use repo UnitRepository
    public function create() : UserCreateResponse
    {
        $response = new UserCreateResponse();

        $response->units = $this->unitRepository->find__all();

        return $response;
    }
}
