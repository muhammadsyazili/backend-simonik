<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\RealizationPaperWorkEditResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;

class RealizationPaperWorkService {

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

    //use repo UserRepository, LevelRepository, UnitRepository, IndicatorRepository
    public function edit(string|int $userId, string $level, string $unit, string $year) : RealizationPaperWorkEditResponse
    {
        $response = new RealizationPaperWorkEditResponse();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $this->userRepository;
        $constructRequest->levelRepository = $this->levelRepository;

        $levelService = new LevelService($constructRequest);

        $response->levels = $levelService->levelsOfUser($userId, false);

        $levelId = $this->levelRepository->findIdBySlug($level);

        $response->indicators = $this->indicatorRepository->findAllWithChildsAndTargetsAndRealizationsByLevelIdAndUnitIdAndYear($levelId, $this->unitRepository->findIdBySlug($unit), $year);

        return $response;
    }
}
