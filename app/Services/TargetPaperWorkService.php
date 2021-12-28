<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\TargetPaperWorkEditResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;

class TargetPaperWorkService {

    private ?UserRepository $userRepository;
    private ?LevelRepository $levelRepository;
    private ?IndicatorRepository $indicatorRepository;
    private ?UnitRepository $unitRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->userRepository = $constructRequest->userRepository;
        $this->levelRepository = $constructRequest->levelRepository;
        $this->indicatorRepository = $constructRequest->indicatorRepository;
        $this->unitRepository = $constructRequest->unitRepository;
    }

    public function edit(string|int $userId, string $level, string $unit, string $year) : TargetPaperWorkEditResponse
    {
        $response = new TargetPaperWorkEditResponse();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $this->userRepository;
        $constructRequest->levelRepository = $this->levelRepository;

        $levelService = new LevelService($constructRequest);

        $response->levels = $levelService->levelsOfUser($userId, false);

        //$user = $this->userRepository->findWithRoleUnitLevelById($userId);

        //$parent_id = $user->role->name === 'super-admin' ? $this->levelRepository->findIdBySlug('super-master') : $user->unit->level->id;

        //$response->levels = $this->levelRepository->findAllWithChildsByParentId($parent_id);

        $response->indicators = $this->indicatorRepository->findAllWithChildsTargetsRealizationsByWhere([
            'level_id' => $this->levelRepository->findIdBySlug($level),
            'label' => $unit === 'master' ? 'master' : 'child',
            'unit_id' => $unit === 'master' ? null : $this->unitRepository->findIdBySlug($unit),
            'year' => $year
        ]);

        return $response;
    }
}
