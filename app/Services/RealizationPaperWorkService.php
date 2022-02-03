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

    //use repo UserRepository, LevelRepository, UnitRepository, IndicatorRepository, RealizationRepository
    public function update(string|int $userId, array $indicators, array $realizations, string $level, string $unit, string $year) : void
    {
        //jika user adalah 'super-admin' or 'admin' maka bisa entry realisasi semua bulan, else hanya bisa bulan saat ini or bulan yang un-locked
        DB::transaction(function () use ($userId, $indicators, $realizations, $level, $unit, $year) {
            $user = $this->userRepository->findWithRoleUnitLevelById($userId);

            $levelId = $this->levelRepository->findIdBySlug($level);

            $indicators = $this->indicatorRepository->findAllByLevelIdAndUnitIdAndYearAndIdList($indicators, $levelId, $this->unitRepository->findIdBySlug($unit), $year);

            foreach ($indicators as $indicator) {
                //section: paper work 'CHILD' updating ----------------------------------------------------------------------
                foreach ($indicator->validity as $month => $value) {
                    if (in_array($user->role->name, ['super-admin', 'admin'])) {
                        $this->realizationRepository->updateValueAndDefaultByMonthAndIndicatorId($month, $indicator->id, $realizations[$indicator->id][$month]);
                    } else {
                        if ($this->monthName__to__monthNumber($month) === now()->month || !$this->realizationRepository->findByIndicatorIdAndMonth($indicator->id, $month)->locked) {
                            $this->realizationRepository->updateValueAndDefaultByMonthAndIndicatorId($month, $indicator->id, $realizations[$indicator->id][$month]);
                        }
                    }
                }
                //end section: paper work 'CHILD' updating ----------------------------------------------------------------------
            }
        });
    }

    public function changeLock(string|int $userId, string|int $indicatorId, string $month)
    {

    }

    private function monthName__to__monthNumber(string $monthName) : int
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
