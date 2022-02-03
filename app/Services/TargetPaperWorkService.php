<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\TargetPaperWorkEditResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\TargetRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class TargetPaperWorkService {

    private ?UserRepository $userRepository;
    private ?LevelRepository $levelRepository;
    private ?IndicatorRepository $indicatorRepository;
    private ?UnitRepository $unitRepository;
    private ?TargetRepository $targetRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->userRepository = $constructRequest->userRepository;
        $this->levelRepository = $constructRequest->levelRepository;
        $this->indicatorRepository = $constructRequest->indicatorRepository;
        $this->unitRepository = $constructRequest->unitRepository;
        $this->targetRepository = $constructRequest->targetRepository;
    }

    //use repo UserRepository, LevelRepository, UnitRepository, IndicatorRepository
    public function edit(string|int $userId, string $level, string $unit, string $year) : TargetPaperWorkEditResponse
    {
        $response = new TargetPaperWorkEditResponse();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $this->userRepository;
        $constructRequest->levelRepository = $this->levelRepository;

        $levelService = new LevelService($constructRequest);

        $response->levels = $levelService->levelsOfUser($userId, false);

        $levelId = $this->levelRepository->findIdBySlug($level);

        $response->indicators = $unit === 'master' ?
        $this->indicatorRepository->findAllWithChildsAndTargetsAndRealizationsByLevelIdAndUnitIdAndYear($levelId, null, $year) :
        $this->indicatorRepository->findAllWithChildsAndTargetsAndRealizationsByLevelIdAndUnitIdAndYear($levelId, $this->unitRepository->findIdBySlug($unit), $year);

        return $response;
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository, TargetRepository
    public function update(string|int $userId, array $indicators, array $targets, string $level, string $unit, string $year) : void
    {
        DB::transaction(function () use ($userId, $indicators, $targets, $level, $unit, $year) {
            $user = $this->userRepository->findWithRoleUnitLevelById($userId);

            $levelId = $this->levelRepository->findIdBySlug($level);
            $indicators = $unit === 'master' ? $this->indicatorRepository->findAllByLevelIdAndUnitIdAndYearAndIdList($indicators, $levelId, null, $year) : $this->indicatorRepository->findAllByLevelIdAndUnitIdAndYearAndIdList($indicators, $levelId, $this->unitRepository->findIdBySlug($unit), $year);

            foreach ($indicators as $indicator) {
                //section: paper work 'MASTER' updating ----------------------------------------------------------------------
                foreach ($indicator->validity as $month => $value) {
                    $this->targetRepository->updateValueAndDefaultByMonthAndIndicatorId($month, $indicator->id, $targets[$indicator->id][$month]);
                }
                //end section: paper work 'MASTER' updating ----------------------------------------------------------------------

                if ($unit === 'master') {
                    $indicatorsChild = $this->indicatorRepository->findAllByParentVerticalId($indicator->id);

                    if ($user->role->name === 'super-admin') {
                        //section: paper work 'CHILD' updating ----------------------------------------------------------------------
                        foreach ($indicatorsChild as $indicatorChild) {
                            foreach ($indicatorChild->validity as $month => $value) {
                                if (in_array($month, array_keys($targets[$indicator->id]))) {
                                    $this->targetRepository->updateValueAndDefaultByMonthAndIndicatorId($month, $indicatorChild->id, $targets[$indicator->id][$month]);
                                }
                            }
                        }
                        //end section: paper work 'CHILD' updating ----------------------------------------------------------------------
                    } else {
                        $unitsId = Arr::flatten($this->unitRepository->findAllIdWithChildsById($user->unit->id)); //mengambil daftar 'id' unit-unit turunan berdasarkan user

                        //section: paper work 'CHILD' updating ----------------------------------------------------------------------
                        foreach ($indicatorsChild as $indicatorChild) {
                            if (in_array($indicatorChild->unit_id, $unitsId)) {
                                foreach ($indicatorChild->validity as $month => $value) {
                                    if (in_array($month, array_keys($targets[$indicator->id]))) {
                                        $this->targetRepository->updateValueAndDefaultByMonthAndIndicatorId($month, $indicatorChild->id, $targets[$indicator->id][$month]);
                                    }
                                }
                            }
                        }
                        //end section: paper work 'CHILD' updating ----------------------------------------------------------------------
                    }
                }
            }
        });
    }
}
