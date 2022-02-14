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

class TargetPaperWorkService
{

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
    public function edit(string|int $userId, string $level, string $unit, string $year): TargetPaperWorkEditResponse
    {
        $response = new TargetPaperWorkEditResponse();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $this->userRepository;
        $constructRequest->levelRepository = $this->levelRepository;

        $levelService = new LevelService($constructRequest);

        $response->levels = $levelService->levelsOfUser($userId, false);

        $levelId = $this->levelRepository->find__id__by__slug($level);

        $response->indicators = $unit === 'master' ?
            $this->indicatorRepository->find__all__with__childs_targets_realizations__by__levelId_unitId_year($levelId, null, $year) :
            $this->indicatorRepository->find__all__with__childs_targets_realizations__by__levelId_unitId_year($levelId, $this->unitRepository->find__id__by__slug($unit), $year);

        return $response;
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository, TargetRepository
    public function update(string|int $userId, array $indicators, array $targets, string $level, string $unit, string $year): void
    {
        DB::transaction(function () use ($userId, $indicators, $targets, $level, $unit, $year) {
            $user = $this->userRepository->find__with__role_unit_level__by__id($userId);

            $levelId = $this->levelRepository->find__id__by__slug($level);
            $indicators = $unit === 'master' ? $this->indicatorRepository->find__all__by__idList_levelId_unitId_year($indicators, $levelId, null, $year) : $this->indicatorRepository->find__all__by__idList_levelId_unitId_year($indicators, $levelId, $this->unitRepository->find__id__by__slug($unit), $year);

            foreach ($indicators as $indicator) {
                //section: paper work 'MASTER' updating ----------------------------------------------------------------------
                foreach ($indicator->validity as $month => $value) {
                    $target = $this->targetRepository->find__by__indicatorId_month($indicator->id, $month);

                    if ($target->value != $targets[$indicator->id][$month]) {
                        $this->targetRepository->update__value_default__by__month_indicatorId($month, $indicator->id, $targets[$indicator->id][$month]);
                    }
                }
                //end section: paper work 'MASTER' updating ----------------------------------------------------------------------

                if ($unit === 'master') {
                    $indicatorsChild = $this->indicatorRepository->findAllByParentVerticalId($indicator->id);

                    if ($user->role->name === 'super-admin') {
                        //section: paper work 'CHILD' updating ----------------------------------------------------------------------
                        foreach ($indicatorsChild as $indicatorChild) {
                            foreach ($indicatorChild->validity as $month => $value) {
                                if (in_array($month, array_keys($targets[$indicator->id]))) {
                                    $target = $this->targetRepository->find__by__indicatorId_month($indicatorChild->id, $month);

                                    if ($target->value != $targets[$indicator->id][$month]) {
                                        $this->targetRepository->update__value_default__by__month_indicatorId($month, $indicatorChild->id, $targets[$indicator->id][$month]);
                                    }
                                }
                            }
                        }
                        //end section: paper work 'CHILD' updating ----------------------------------------------------------------------
                    } else {
                        $unitsId = $this->unitRepository->find__allFlattenId__with__childs__by__id($user->unit->id); //mengambil daftar 'id' unit-unit turunan berdasarkan user

                        //section: paper work 'CHILD' updating ----------------------------------------------------------------------
                        foreach ($indicatorsChild as $indicatorChild) {
                            if (in_array($indicatorChild->unit_id, $unitsId)) {
                                foreach ($indicatorChild->validity as $month => $value) {
                                    if (in_array($month, array_keys($targets[$indicator->id]))) {
                                        $target = $this->targetRepository->find__by__indicatorId_month($indicatorChild->id, $month);

                                        if ($target->value != $targets[$indicator->id][$month]) {
                                            $this->targetRepository->update__value_default__by__month_indicatorId($month, $indicatorChild->id, $targets[$indicator->id][$month]);
                                        }
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
