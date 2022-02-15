<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\RealizationPaperWorkCreateOrEditResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;

class RealizationPaperWorkService
{

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
    public function edit(string|int $userId, string $level, string $unit, string $year): RealizationPaperWorkCreateOrEditResponse
    {
        $response = new RealizationPaperWorkCreateOrEditResponse();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $this->userRepository;
        $constructRequest->levelRepository = $this->levelRepository;

        $levelService = new LevelService($constructRequest);

        $response->levels = $levelService->levelsOfUser($userId, false);

        $levelId = $this->levelRepository->find__id__by__slug($level);

        $response->indicators = $this->indicatorRepository->find__all__with__childs_targets_realizations__by__levelId_unitId_year($levelId, $this->unitRepository->find__id__by__slug($unit), $year);

        return $response;
    }

    //use repo UserRepository, LevelRepository, UnitRepository, IndicatorRepository, RealizationRepository
    public function update(string|int $userId, array $indicators, array $realizations, string $level, string $unit, string $year): void
    {
        //jika user adalah 'super-admin' or 'admin' maka bisa entry realisasi semua bulan, else hanya bisa bulan saat ini or bulan yang un-locked
        DB::transaction(function () use ($userId, $indicators, $realizations, $level, $unit, $year) {
            $user = $this->userRepository->find__with__role_unit_level__by__id($userId);

            $levelId = $this->levelRepository->find__id__by__slug($level);

            $indicators = $this->indicatorRepository->find__all__by__idList_levelId_unitId_year($indicators, $levelId, $this->unitRepository->find__id__by__slug($unit), $year);

            foreach ($indicators as $indicator) {
                //section: paper work 'CHILD' updating ----------------------------------------------------------------------
                foreach ($indicator->validity as $month => $value) {
                    $realization = $this->realizationRepository->find__by__indicatorId_month($indicator->id, $month);

                    if (in_array($user->role->name, ['super-admin', 'admin'])) {
                        if ($realization->value != $realizations[$indicator->id][$month]) {
                            $this->realizationRepository->update__value_default__by__month_indicatorId($month, $indicator->id, $realizations[$indicator->id][$month]);
                        }
                    } else {
                        if ($this->monthName__to__monthNumber($month) === now()->month || !$this->realizationRepository->find__by__indicatorId_month($indicator->id, $month)->locked) {
                            if ($realization->value != $realizations[$indicator->id][$month]) {
                                $this->realizationRepository->update__value_default__by__month_indicatorId($month, $indicator->id, $realizations[$indicator->id][$month]);
                            }
                        }
                    }
                }
                //end section: paper work 'CHILD' updating ----------------------------------------------------------------------
            }
        });
    }

    //use repo RealizationRepository
    public function changeLock(string|int $indicatorId, string $month): void
    {
        DB::transaction(function () use ($indicatorId, $month) {
            $realization = $this->realizationRepository->find__by__indicatorId_month($indicatorId, $month);

            if ($realization->locked) {
                $this->realizationRepository->update__locked__by__month_indicatorId($month, $indicatorId, false);
            } else {
                $this->realizationRepository->update__locked__by__month_indicatorId($month, $indicatorId, true);
            }
        });
    }

    private function monthName__to__monthNumber(string $monthName): int
    {
        $monthNumber = 1;

        switch ($monthName) {
            case "jan":
                $monthNumber = 1;
                break;
            case "feb":
                $monthNumber = 2;
                break;
            case "mar":
                $monthNumber = 3;
                break;
            case "apr":
                $monthNumber = 4;
                break;
            case "may":
                $monthNumber = 5;
                break;
            case "jun":
                $monthNumber = 6;
                break;
            case "jul":
                $monthNumber = 7;
                break;
            case "aug":
                $monthNumber = 8;
                break;
            case "sep":
                $monthNumber = 9;
                break;
            case "oct":
                $monthNumber = 10;
                break;
            case "nov":
                $monthNumber = 11;
                break;
            case "dec":
                $monthNumber = 12;
                break;
        }

        return $monthNumber;
    }
}
