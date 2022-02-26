<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\TargetPaperWorkCreateOrEditResponse;
use App\DTO\TargetPaperWorkEditRequest;
use App\DTO\TargetPaperWorkUpdateRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\TargetRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class TargetPaperWorkService
{
    private ?UserRepository $userRepository;
    private ?LevelRepository $levelRepository;
    private ?IndicatorRepository $indicatorRepository;
    private ?UnitRepository $unitRepository;
    private ?TargetRepository $targetRepository;

    private mixed $indicators = null;
    private int $iter = 0;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->userRepository = $constructRequest->userRepository;
        $this->levelRepository = $constructRequest->levelRepository;
        $this->indicatorRepository = $constructRequest->indicatorRepository;
        $this->unitRepository = $constructRequest->unitRepository;
        $this->targetRepository = $constructRequest->targetRepository;
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository
    public function edit(TargetPaperWorkEditRequest $targetPaperWorkRequest): TargetPaperWorkCreateOrEditResponse
    {
        $response = new TargetPaperWorkCreateOrEditResponse();

        $level = $targetPaperWorkRequest->level;
        $unit = $targetPaperWorkRequest->unit;
        $year = $targetPaperWorkRequest->year;
        $userId = $targetPaperWorkRequest->userId;

        $levelId = $this->levelRepository->find__id__by__slug($level);

        $indicators = $unit === 'master' ?
            $this->indicatorRepository->find__all__with__childs_targets_realizations__by__levelId_unitId_year($levelId, null, $year) :
            $this->indicatorRepository->find__all__with__childs_targets_realizations__by__levelId_unitId_year($levelId, $this->unitRepository->find__id__by__slug($unit), $year);

        $this->mapping__edit__indicators($indicators, ['r' => 255, 'g' => 255, 'b' => 255]);

        //dd($this->indicators);

        $response->indicators = $this->indicators;

        return $response;
    }

    private function mapping__edit__indicators(Collection $indicators, array $bg_color, string $prefix = null, bool $first = true)
    {
        $indicators->each(function ($item, $key) use ($prefix, $first, $bg_color) {
            $prefix = is_null($prefix) ? (string) ($key+1) : (string) $prefix.'.'.($key+1);
            $iteration = $first && $this->iter === 0 ? 0 : $this->iter;

            //dump($iteration.'->'.$prefix.'.'.$item->indicator);

            $jan = $item->targets->search(function ($value) {
                return $value->month === 'jan';
            });
            $this->indicators[$iteration]['targets']['jan']['value'] = $jan === false ? null : $item->targets[$jan]->value;
            $this->indicators[$iteration]['targets']['jan']['updated_at'] = $jan === false ? null : Carbon::parse($item->targets[$jan]->updated_at)->format('d/m/Y H:i:s');

            $feb = $item->targets->search(function ($value) {
                return $value->month === 'feb';
            });
            $this->indicators[$iteration]['targets']['feb']['value'] = $feb === false ? null : $item->targets[$feb]->value;
            $this->indicators[$iteration]['targets']['feb']['updated_at'] = $feb === false ? null : Carbon::parse($item->targets[$feb]->updated_at)->format('d/m/Y H:i:s');

            $mar = $item->targets->search(function ($value) {
                return $value->month === 'mar';
            });
            $this->indicators[$iteration]['targets']['mar']['value'] = $mar === false ? null : $item->targets[$mar]->value;
            $this->indicators[$iteration]['targets']['mar']['updated_at'] = $mar === false ? null : Carbon::parse($item->targets[$mar]->updated_at)->format('d/m/Y H:i:s');

            $apr = $item->targets->search(function ($value) {
                return $value->month === 'apr';
            });
            $this->indicators[$iteration]['targets']['apr']['value'] = $apr === false ? null : $item->targets[$apr]->value;
            $this->indicators[$iteration]['targets']['apr']['updated_at'] = $apr === false ? null : Carbon::parse($item->targets[$apr]->updated_at)->format('d/m/Y H:i:s');

            $may = $item->targets->search(function ($value) {
                return $value->month === 'may';
            });
            $this->indicators[$iteration]['targets']['may']['value'] = $may === false ? null : $item->targets[$may]->value;
            $this->indicators[$iteration]['targets']['may']['updated_at'] = $may === false ? null : Carbon::parse($item->targets[$may]->updated_at)->format('d/m/Y H:i:s');

            $jun = $item->targets->search(function ($value) {
                return $value->month === 'jun';
            });
            $this->indicators[$iteration]['targets']['jun']['value'] = $jun === false ? null : $item->targets[$jun]->value;
            $this->indicators[$iteration]['targets']['jun']['updated_at'] = $jun === false ? null : Carbon::parse($item->targets[$jun]->updated_at)->format('d/m/Y H:i:s');

            $jul = $item->targets->search(function ($value) {
                return $value->month === 'jul';
            });
            $this->indicators[$iteration]['targets']['jul']['value'] = $jul === false ? null : $item->targets[$jul]->value;
            $this->indicators[$iteration]['targets']['jul']['updated_at'] = $jul === false ? null : Carbon::parse($item->targets[$jul]->updated_at)->format('d/m/Y H:i:s');

            $aug = $item->targets->search(function ($value) {
                return $value->month === 'aug';
            });
            $this->indicators[$iteration]['targets']['aug']['value'] = $aug === false ? null : $item->targets[$aug]->value;
            $this->indicators[$iteration]['targets']['aug']['updated_at'] = $aug === false ? null : Carbon::parse($item->targets[$aug]->updated_at)->format('d/m/Y H:i:s');

            $sep = $item->targets->search(function ($value) {
                return $value->month === 'sep';
            });
            $this->indicators[$iteration]['targets']['sep']['value'] = $sep === false ? null : $item->targets[$sep]->value;
            $this->indicators[$iteration]['targets']['sep']['updated_at'] = $sep === false ? null : Carbon::parse($item->targets[$sep]->updated_at)->format('d/m/Y H:i:s');

            $oct = $item->targets->search(function ($value) {
                return $value->month === 'oct';
            });
            $this->indicators[$iteration]['targets']['oct']['value'] = $oct === false ? null : $item->targets[$oct]->value;
            $this->indicators[$iteration]['targets']['oct']['updated_at'] = $oct === false ? null : Carbon::parse($item->targets[$oct]->updated_at)->format('d/m/Y H:i:s');

            $nov = $item->targets->search(function ($value) {
                return $value->month === 'nov';
            });
            $this->indicators[$iteration]['targets']['nov']['value'] = $nov === false ? null : $item->targets[$nov]->value;
            $this->indicators[$iteration]['targets']['nov']['updated_at'] = $nov === false ? null : Carbon::parse($item->targets[$nov]->updated_at)->format('d/m/Y H:i:s');

            $dec = $item->targets->search(function ($value) {
                return $value->month === 'dec';
            });
            $this->indicators[$iteration]['targets']['dec']['value'] = $dec === false ? null : $item->targets[$dec]->value;
            $this->indicators[$iteration]['targets']['dec']['updated_at'] = $dec === false ? null : Carbon::parse($item->targets[$dec]->updated_at)->format('d/m/Y H:i:s');

            $this->indicators[$iteration]['id'] = $item->id;
            $this->indicators[$iteration]['indicator'] = $prefix.'.'.$item->indicator;
            $this->indicators[$iteration]['formula'] = $item->formula;
            $this->indicators[$iteration]['measure'] = $item->measure;
            $this->indicators[$iteration]['weight'] = $item->weight;//
            $this->indicators[$iteration]['validity'] = $item->validity;//
            $this->indicators[$iteration]['polarity'] = $item->polarity;
            $this->indicators[$iteration]['order'] = $iteration;
            $this->indicators[$iteration]['bg_color'] = $bg_color;

            $this->iter++;

            if (!empty($item->childsHorizontalRecursive)) {
                $this->mapping__edit__indicators($item->childsHorizontalRecursive, ['r' => $bg_color['r']-15, 'g' => $bg_color['g']-15, 'b' => $bg_color['b']-15], $prefix, false);
            }
        });
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository, TargetRepository
    public function update(TargetPaperWorkUpdateRequest $targetPaperWorkRequest): void
    {
        $userId = $targetPaperWorkRequest->userId;
        $indicators = $targetPaperWorkRequest->indicators;
        $targets = $targetPaperWorkRequest->targets;
        $level = $targetPaperWorkRequest->level;
        $unit = $targetPaperWorkRequest->unit;
        $year = $targetPaperWorkRequest->year;

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
