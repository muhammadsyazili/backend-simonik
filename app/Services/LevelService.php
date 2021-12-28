<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\LevelRepository;
use App\Repositories\UserRepository;

class LevelService {

    private ?LevelRepository $levelRepository;
    private ?UserRepository $userRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->levelRepository = $constructRequest->levelRepository;
        $this->userRepository = $constructRequest->userRepository;
    }

    public function levelsOfUser(string|int $id, bool $withSuperMaster)
    {
        $user = $this->userRepository->findWithRoleUnitLevelById($id);

        $levels = null;
        if ($user->role->name === 'super-admin') {
            $levels = $withSuperMaster ? $this->levelRepository->findAllWithChildsByRoot() : $this->levelRepository->findAllWithChildsByParentId($this->levelRepository->findIdBySlug('super-master'));
        } else {
            $levels = $this->levelRepository->findAllWithChildsById($user->unit->level->id);
        }

        return $levels;
    }
}
