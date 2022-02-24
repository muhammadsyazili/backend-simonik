<?php

namespace App\Services;

use App\DTO\AnalyticIndexRequest;
use App\DTO\AnalyticIndexResponse;
use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;

class AnalyticService
{
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
    public function index(AnalyticIndexRequest $analyticRequest): AnalyticIndexResponse
    {
        $response = new AnalyticIndexResponse();

        $level = $analyticRequest->level;
        $unit = $analyticRequest->unit;
        $year = $analyticRequest->year;
        $month = $analyticRequest->month;
        $userId = $analyticRequest->userId;

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $this->userRepository;
        $constructRequest->levelRepository = $this->levelRepository;

        $levelService = new LevelService($constructRequest);

        $response->levels = $levelService->levels_of_user($userId, false);

        $levelId = $this->levelRepository->find__id__by__slug($level);
        $unitId = $this->unitRepository->find__id__by__slug($unit);

        $response->indicators = $this->indicatorRepository->find__all__with__childs_targets_realizations__by__levelId_unitId_year($levelId, $unitId, $year);

        return $response;
    }
}
