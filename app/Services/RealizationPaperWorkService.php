<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\RealizationPaperWorkEditResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;

class RealizationPaperWorkService {

    private ?UserRepository $userRepository;
    private ?LevelRepository $levelRepository;
    private ?IndicatorRepository $indicatorRepository;
    private ?UnitRepository $unitRepository;
    private ?RealizationRepository $realizationRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->userRepository = $constructRequest->userRepository;
        $this->levelRepository = $constructRequest->levelRepository;
        $this->indicatorRepository = $constructRequest->indicatorRepository;
        $this->unitRepository = $constructRequest->unitRepository;
        $this->realizationRepository = $constructRequest->realizationRepository;
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

    public function update(string|int $userId, array $indicators, array $realizations, string $level, string $unit, string $year) : void
    {
        DB::transaction(function () use ($userId, $indicators, $realizations, $level, $unit, $year) {
            $levelId = $this->levelRepository->findIdBySlug($level);

            $indicators = $this->indicatorRepository->findAllByLevelIdAndUnitIdAndYearAndIdList($indicators, $levelId, $this->unitRepository->findIdBySlug($unit), $year);

            foreach ($indicators as $indicator) {
                //section: paper work 'MASTER' updating ----------------------------------------------------------------------
                foreach ($indicator->validity as $validityK => $validityV) {
                    $this->realizationRepository->updateValueAndDefaultByMonthAndIndicatorId($validityK, $indicator->id, $realizations[$indicator->id][$validityK]);
                }
                //end section: paper work 'MASTER' updating ----------------------------------------------------------------------
            }
        });
    }
}
