<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\RealizationPaperWorkChangeLockRequest;
use App\DTO\RealizationPaperWorkEditRequest;
use App\DTO\RealizationPaperWorkEditResponse;
use App\DTO\RealizationPaperWorkUpdateRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class RealizationPaperWorkService
{
    private ?UserRepository $userRepository;
    private ?LevelRepository $levelRepository;
    private ?IndicatorRepository $indicatorRepository;
    private ?UnitRepository $unitRepository;
    private ?RealizationRepository $realizationRepository;

    private mixed $indicators = null;
    private int $iter = 0;
    private string $role;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->userRepository = $constructRequest->userRepository;
        $this->levelRepository = $constructRequest->levelRepository;
        $this->indicatorRepository = $constructRequest->indicatorRepository;
        $this->unitRepository = $constructRequest->unitRepository;
        $this->realizationRepository = $constructRequest->realizationRepository;
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository
    public function edit(RealizationPaperWorkEditRequest $realizationPaperWorkRequest): RealizationPaperWorkEditResponse
    {
        $response = new RealizationPaperWorkEditResponse();

        $level = $realizationPaperWorkRequest->level;
        $unit = $realizationPaperWorkRequest->unit;
        $year = $realizationPaperWorkRequest->year;
        $userId = $realizationPaperWorkRequest->userId;

        $user = $this->userRepository->find__with__role_unit_level__by__id($userId);

        $this->role = $user->role->name;

        $levelId = $this->levelRepository->find__id__by__slug($level);
        $unitId = $this->unitRepository->find__id__by__slug($unit);

        $indicators = $this->indicatorRepository->find__all__with__childs_targets_realizations__by__levelId_unitId_year($levelId, $unitId, $year);

        $this->iter = 0; //reset iterator
        $this->mapping__edit__indicators($indicators, $year, ['r' => 255, 'g' => 255, 'b' => 255]);

        $response->indicators = $this->indicators;

        return $response;
    }

    private function mapping__edit__indicators(Collection $indicators, $year, array $bg_color, string $prefix = null, bool $first = true): void
    {
        $indicators->each(function ($item, $key) use ($year, $prefix, $first, $bg_color) {
            $prefix = is_null($prefix) ? (string) ($key + 1) : (string) $prefix . '.' . ($key + 1);
            $iteration = $first && $this->iter === 0 ? 0 : $this->iter;
            $indicator = $item->indicator;

            $this->indicators[$iteration]['id'] = $item->id;
            $this->indicators[$iteration]['indicator'] = "$prefix. $indicator";
            $this->indicators[$iteration]['type'] = $item->type;
            $this->indicators[$iteration]['formula'] = $item->formula;
            $this->indicators[$iteration]['measure'] = $item->measure;
            $this->indicators[$iteration]['weight'] = $item->weight;
            $this->indicators[$iteration]['validity'] = $item->validity;
            $this->indicators[$iteration]['polarity'] = $item->polarity;
            $this->indicators[$iteration]['order'] = $iteration;
            $this->indicators[$iteration]['bg_color'] = $bg_color;
            $this->indicators[$iteration]['change_lock'] = in_array($this->role, ['super-admin', 'admin']) ? true : false;

            //target
            $jan = $item->targets->search(function ($value) {
                return $value->month === 'jan';
            });
            $this->indicators[$iteration]['targets']['jan']['value'] = $jan === false ? null : $item->targets[$jan]->value;

            $feb = $item->targets->search(function ($value) {
                return $value->month === 'feb';
            });
            $this->indicators[$iteration]['targets']['feb']['value'] = $feb === false ? null : $item->targets[$feb]->value;

            $mar = $item->targets->search(function ($value) {
                return $value->month === 'mar';
            });
            $this->indicators[$iteration]['targets']['mar']['value'] = $mar === false ? null : $item->targets[$mar]->value;

            $apr = $item->targets->search(function ($value) {
                return $value->month === 'apr';
            });
            $this->indicators[$iteration]['targets']['apr']['value'] = $apr === false ? null : $item->targets[$apr]->value;

            $may = $item->targets->search(function ($value) {
                return $value->month === 'may';
            });
            $this->indicators[$iteration]['targets']['may']['value'] = $may === false ? null : $item->targets[$may]->value;

            $jun = $item->targets->search(function ($value) {
                return $value->month === 'jun';
            });
            $this->indicators[$iteration]['targets']['jun']['value'] = $jun === false ? null : $item->targets[$jun]->value;

            $jul = $item->targets->search(function ($value) {
                return $value->month === 'jul';
            });
            $this->indicators[$iteration]['targets']['jul']['value'] = $jul === false ? null : $item->targets[$jul]->value;

            $aug = $item->targets->search(function ($value) {
                return $value->month === 'aug';
            });
            $this->indicators[$iteration]['targets']['aug']['value'] = $aug === false ? null : $item->targets[$aug]->value;

            $sep = $item->targets->search(function ($value) {
                return $value->month === 'sep';
            });
            $this->indicators[$iteration]['targets']['sep']['value'] = $sep === false ? null : $item->targets[$sep]->value;

            $oct = $item->targets->search(function ($value) {
                return $value->month === 'oct';
            });
            $this->indicators[$iteration]['targets']['oct']['value'] = $oct === false ? null : $item->targets[$oct]->value;

            $nov = $item->targets->search(function ($value) {
                return $value->month === 'nov';
            });
            $this->indicators[$iteration]['targets']['nov']['value'] = $nov === false ? null : $item->targets[$nov]->value;

            $dec = $item->targets->search(function ($value) {
                return $value->month === 'dec';
            });
            $this->indicators[$iteration]['targets']['dec']['value'] = $dec === false ? null : $item->targets[$dec]->value;

            //realisasi
            $jan = $item->realizations->search(function ($value) {
                return $value->month === 'jan';
            });
            $this->indicators[$iteration]['realizations']['jan']['value'] = $jan === false ? null : $item->realizations[$jan]->value;
            $this->indicators[$iteration]['realizations']['jan']['updated_at'] = $jan === false ? null : Carbon::parse($item->realizations[$jan]->updated_at)->format('d/m/Y H:i:s');
            $this->indicators[$iteration]['realizations']['jan']['locked'] = $jan === false ? null : $item->realizations[$jan]->locked;
            $this->indicators[$iteration]['realizations']['jan']['status'] = $jan === false ? null : ($item->realizations[$jan]->default ? 'D' : 'M');
            $this->indicators[$iteration]['realizations']['jan']['readonly'] = $jan === false ? null : ((!in_array($this->role, ['super-admin', 'admin']) && $item->realizations[$jan]->locked && now()->month-1 !== 1) || ($year != now()->year) ? true : false);

            $feb = $item->realizations->search(function ($value) {
                return $value->month === 'feb';
            });
            $this->indicators[$iteration]['realizations']['feb']['value'] = $feb === false ? null : $item->realizations[$feb]->value;
            $this->indicators[$iteration]['realizations']['feb']['updated_at'] = $feb === false ? null : Carbon::parse($item->realizations[$feb]->updated_at)->format('d/m/Y H:i:s');
            $this->indicators[$iteration]['realizations']['feb']['locked'] = $feb === false ? null : $item->realizations[$feb]->locked;
            $this->indicators[$iteration]['realizations']['feb']['status'] = $feb === false ? null : ($item->realizations[$feb]->default ? 'D' : 'M');
            $this->indicators[$iteration]['realizations']['feb']['readonly'] = $feb === false ? null : ((!in_array($this->role, ['super-admin', 'admin']) && $item->realizations[$feb]->locked && now()->month-1 !== 2) || ($year != now()->year) ? true : false);

            $mar = $item->realizations->search(function ($value) {
                return $value->month === 'mar';
            });
            $this->indicators[$iteration]['realizations']['mar']['value'] = $mar === false ? null : $item->realizations[$mar]->value;
            $this->indicators[$iteration]['realizations']['mar']['updated_at'] = $mar === false ? null : Carbon::parse($item->realizations[$mar]->updated_at)->format('d/m/Y H:i:s');
            $this->indicators[$iteration]['realizations']['mar']['locked'] = $mar === false ? null : $item->realizations[$mar]->locked;
            $this->indicators[$iteration]['realizations']['mar']['status'] = $mar === false ? null : ($item->realizations[$mar]->default ? 'D' : 'M');
            $this->indicators[$iteration]['realizations']['mar']['readonly'] = $mar === false ? null : ((!in_array($this->role, ['super-admin', 'admin']) && $item->realizations[$mar]->locked && now()->month-1 !== 3) || ($year != now()->year) ? true : false);

            $apr = $item->realizations->search(function ($value) {
                return $value->month === 'apr';
            });
            $this->indicators[$iteration]['realizations']['apr']['value'] = $apr === false ? null : $item->realizations[$apr]->value;
            $this->indicators[$iteration]['realizations']['apr']['updated_at'] = $apr === false ? null : Carbon::parse($item->realizations[$apr]->updated_at)->format('d/m/Y H:i:s');
            $this->indicators[$iteration]['realizations']['apr']['locked'] = $apr === false ? null : $item->realizations[$apr]->locked;
            $this->indicators[$iteration]['realizations']['apr']['status'] = $apr === false ? null : ($item->realizations[$apr]->default ? 'D' : 'M');
            $this->indicators[$iteration]['realizations']['apr']['readonly'] = $apr === false ? null : ((!in_array($this->role, ['super-admin', 'admin']) && $item->realizations[$apr]->locked && now()->month-1 !== 4) || ($year != now()->year) ? true : false);

            $may = $item->realizations->search(function ($value) {
                return $value->month === 'may';
            });
            $this->indicators[$iteration]['realizations']['may']['value'] = $may === false ? null : $item->realizations[$may]->value;
            $this->indicators[$iteration]['realizations']['may']['updated_at'] = $may === false ? null : Carbon::parse($item->realizations[$may]->updated_at)->format('d/m/Y H:i:s');
            $this->indicators[$iteration]['realizations']['may']['locked'] = $may === false ? null : $item->realizations[$may]->locked;
            $this->indicators[$iteration]['realizations']['may']['status'] = $may === false ? null : ($item->realizations[$may]->default ? 'D' : 'M');
            $this->indicators[$iteration]['realizations']['may']['readonly'] = $may === false ? null : ((!in_array($this->role, ['super-admin', 'admin']) && $item->realizations[$may]->locked && now()->month-1 !== 5) || ($year != now()->year) ? true : false);

            $jun = $item->realizations->search(function ($value) {
                return $value->month === 'jun';
            });
            $this->indicators[$iteration]['realizations']['jun']['value'] = $jun === false ? null : $item->realizations[$jun]->value;
            $this->indicators[$iteration]['realizations']['jun']['updated_at'] = $jun === false ? null : Carbon::parse($item->realizations[$jun]->updated_at)->format('d/m/Y H:i:s');
            $this->indicators[$iteration]['realizations']['jun']['locked'] = $jun === false ? null : $item->realizations[$jun]->locked;
            $this->indicators[$iteration]['realizations']['jun']['status'] = $jun === false ? null : ($item->realizations[$jun]->default ? 'D' : 'M');
            $this->indicators[$iteration]['realizations']['jun']['readonly'] = $jun === false ? null : ((!in_array($this->role, ['super-admin', 'admin']) && $item->realizations[$jun]->locked && now()->month-1 !== 6) || ($year != now()->year) ? true : false);

            $jul = $item->realizations->search(function ($value) {
                return $value->month === 'jul';
            });
            $this->indicators[$iteration]['realizations']['jul']['value'] = $jul === false ? null : $item->realizations[$jul]->value;
            $this->indicators[$iteration]['realizations']['jul']['updated_at'] = $jul === false ? null : Carbon::parse($item->realizations[$jul]->updated_at)->format('d/m/Y H:i:s');
            $this->indicators[$iteration]['realizations']['jul']['locked'] = $jul === false ? null : $item->realizations[$jul]->locked;
            $this->indicators[$iteration]['realizations']['jul']['status'] = $jul === false ? null : ($item->realizations[$jul]->default ? 'D' : 'M');
            $this->indicators[$iteration]['realizations']['jul']['readonly'] = $jul === false ? null : ((!in_array($this->role, ['super-admin', 'admin']) && $item->realizations[$jul]->locked && now()->month-1 !== 7) || ($year != now()->year) ? true : false);

            $aug = $item->realizations->search(function ($value) {
                return $value->month === 'aug';
            });
            $this->indicators[$iteration]['realizations']['aug']['value'] = $aug === false ? null : $item->realizations[$aug]->value;
            $this->indicators[$iteration]['realizations']['aug']['updated_at'] = $aug === false ? null : Carbon::parse($item->realizations[$aug]->updated_at)->format('d/m/Y H:i:s');
            $this->indicators[$iteration]['realizations']['aug']['locked'] = $aug === false ? null : $item->realizations[$aug]->locked;
            $this->indicators[$iteration]['realizations']['aug']['status'] = $aug === false ? null : ($item->realizations[$aug]->default ? 'D' : 'M');
            $this->indicators[$iteration]['realizations']['aug']['readonly'] = $aug === false ? null : ((!in_array($this->role, ['super-admin', 'admin']) && $item->realizations[$aug]->locked && now()->month-1 !== 8) || ($year != now()->year) ? true : false);

            $sep = $item->realizations->search(function ($value) {
                return $value->month === 'sep';
            });
            $this->indicators[$iteration]['realizations']['sep']['value'] = $sep === false ? null : $item->realizations[$sep]->value;
            $this->indicators[$iteration]['realizations']['sep']['updated_at'] = $sep === false ? null : Carbon::parse($item->realizations[$sep]->updated_at)->format('d/m/Y H:i:s');
            $this->indicators[$iteration]['realizations']['sep']['locked'] = $sep === false ? null : $item->realizations[$sep]->locked;
            $this->indicators[$iteration]['realizations']['sep']['status'] = $sep === false ? null : ($item->realizations[$sep]->default ? 'D' : 'M');
            $this->indicators[$iteration]['realizations']['sep']['readonly'] = $sep === false ? null : ((!in_array($this->role, ['super-admin', 'admin']) && $item->realizations[$sep]->locked && now()->month-1 !== 9) || ($year != now()->year) ? true : false);

            $oct = $item->realizations->search(function ($value) {
                return $value->month === 'oct';
            });
            $this->indicators[$iteration]['realizations']['oct']['value'] = $oct === false ? null : $item->realizations[$oct]->value;
            $this->indicators[$iteration]['realizations']['oct']['updated_at'] = $oct === false ? null : Carbon::parse($item->realizations[$oct]->updated_at)->format('d/m/Y H:i:s');
            $this->indicators[$iteration]['realizations']['oct']['locked'] = $oct === false ? null : $item->realizations[$oct]->locked;
            $this->indicators[$iteration]['realizations']['oct']['status'] = $oct === false ? null : ($item->realizations[$oct]->default ? 'D' : 'M');
            $this->indicators[$iteration]['realizations']['oct']['readonly'] = $oct === false ? null : ((!in_array($this->role, ['super-admin', 'admin']) && $item->realizations[$oct]->locked && now()->month-1 !== 10) || ($year != now()->year) ? true : false);

            $nov = $item->realizations->search(function ($value) {
                return $value->month === 'nov';
            });
            $this->indicators[$iteration]['realizations']['nov']['value'] = $nov === false ? null : $item->realizations[$nov]->value;
            $this->indicators[$iteration]['realizations']['nov']['updated_at'] = $nov === false ? null : Carbon::parse($item->realizations[$nov]->updated_at)->format('d/m/Y H:i:s');
            $this->indicators[$iteration]['realizations']['nov']['locked'] = $nov === false ? null : $item->realizations[$nov]->locked;
            $this->indicators[$iteration]['realizations']['nov']['status'] = $nov === false ? null : ($item->realizations[$nov]->default ? 'D' : 'M');
            $this->indicators[$iteration]['realizations']['nov']['readonly'] = $nov === false ? null : ((!in_array($this->role, ['super-admin', 'admin']) && $item->realizations[$nov]->locked && now()->month-1 !== 11) || ($year != now()->year) ? true : false);

            $dec = $item->realizations->search(function ($value) {
                return $value->month === 'dec';
            });
            $this->indicators[$iteration]['realizations']['dec']['value'] = $dec === false ? null : $item->realizations[$dec]->value;
            $this->indicators[$iteration]['realizations']['dec']['updated_at'] = $dec === false ? null : Carbon::parse($item->realizations[$dec]->updated_at)->format('d/m/Y H:i:s');
            $this->indicators[$iteration]['realizations']['dec']['locked'] = $dec === false ? null : $item->realizations[$dec]->locked;
            $this->indicators[$iteration]['realizations']['dec']['status'] = $dec === false ? null : ($item->realizations[$dec]->default ? 'D' : 'M');
            $this->indicators[$iteration]['realizations']['dec']['readonly'] = $dec === false ? null : ((!in_array($this->role, ['super-admin', 'admin']) && $item->realizations[$dec]->locked && now()->month-1 !== 12) || ($year != now()->year) ? true : false);

            $this->iter++;

            if (!empty($item->childsHorizontalRecursive)) {
                $this->mapping__edit__indicators($item->childsHorizontalRecursive, $year, ['r' => $bg_color['r'] - 15, 'g' => $bg_color['g'] - 15, 'b' => $bg_color['b'] - 15], $prefix, false);
            }
        });
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository
    public function export(RealizationPaperWorkEditRequest $realizationPaperWorkRequest): RealizationPaperWorkEditResponse
    {
        $response = new RealizationPaperWorkEditResponse();

        $level = $realizationPaperWorkRequest->level;
        $unit = $realizationPaperWorkRequest->unit;
        $year = $realizationPaperWorkRequest->year;
        $userId = $realizationPaperWorkRequest->userId;

        $user = $this->userRepository->find__with__role_unit_level__by__id($userId);

        $this->role = $user->role->name;

        $levelId = $this->levelRepository->find__id__by__slug($level);
        $unitId = $this->unitRepository->find__id__by__slug($unit);

        $indicators = $this->indicatorRepository->find__all__with__childs_targets_realizations__by__levelId_unitId_year($levelId, $unitId, $year);

        $this->iter = 0; //reset iterator
        $this->mapping__export__indicators($indicators);

        $response->indicators = $this->indicators;

        return $response;
    }

    private function mapping__export__indicators(Collection $indicators, string $prefix = null, bool $first = true): void
    {
        $indicators->each(function ($item, $key) use ($prefix, $first) {
            $prefix = is_null($prefix) ? (string) ($key + 1) : (string) $prefix . '.' . ($key + 1);
            $iteration = $first && $this->iter === 0 ? 0 : $this->iter;

            $indicator = $item->indicator;
            $type = $item->type;

            $this->indicators[$iteration]['id'] = $item->id;
            $this->indicators[$iteration]['indicator'] = "$prefix. $indicator ($type)";
            $this->indicators[$iteration]['measure'] = is_null($item->measure) ? '-' : $item->measure;

            //realisasi
            $jan = $item->realizations->search(function ($value) {
                return $value->month === 'jan';
            });
            $this->indicators[$iteration]['realizations_jan'] = $jan === false ? '-' : (string) $item->realizations[$jan]->value;

            $feb = $item->realizations->search(function ($value) {
                return $value->month === 'feb';
            });
            $this->indicators[$iteration]['realizations_feb'] = $feb === false ? '-' : (string) $item->realizations[$feb]->value;

            $mar = $item->realizations->search(function ($value) {
                return $value->month === 'mar';
            });
            $this->indicators[$iteration]['realizations_mar'] = $mar === false ? '-' : (string) $item->realizations[$mar]->value;

            $apr = $item->realizations->search(function ($value) {
                return $value->month === 'apr';
            });
            $this->indicators[$iteration]['realizations_apr'] = $apr === false ? '-' : (string) $item->realizations[$apr]->value;

            $may = $item->realizations->search(function ($value) {
                return $value->month === 'may';
            });
            $this->indicators[$iteration]['realizations_may'] = $may === false ? '-' : (string) $item->realizations[$may]->value;

            $jun = $item->realizations->search(function ($value) {
                return $value->month === 'jun';
            });
            $this->indicators[$iteration]['realizations_jun'] = $jun === false ? '-' : (string) $item->realizations[$jun]->value;

            $jul = $item->realizations->search(function ($value) {
                return $value->month === 'jul';
            });
            $this->indicators[$iteration]['realizations_jul'] = $jul === false ? '-' : (string) $item->realizations[$jul]->value;

            $aug = $item->realizations->search(function ($value) {
                return $value->month === 'aug';
            });
            $this->indicators[$iteration]['realizations_aug'] = $aug === false ? '-' : (string) $item->realizations[$aug]->value;

            $sep = $item->realizations->search(function ($value) {
                return $value->month === 'sep';
            });
            $this->indicators[$iteration]['realizations_sep'] = $sep === false ? '-' : (string) $item->realizations[$sep]->value;

            $oct = $item->realizations->search(function ($value) {
                return $value->month === 'oct';
            });
            $this->indicators[$iteration]['realizations_oct'] = $oct === false ? '-' : (string) $item->realizations[$oct]->value;

            $nov = $item->realizations->search(function ($value) {
                return $value->month === 'nov';
            });
            $this->indicators[$iteration]['realizations_nov'] = $nov === false ? '-' : (string) $item->realizations[$nov]->value;

            $dec = $item->realizations->search(function ($value) {
                return $value->month === 'dec';
            });
            $this->indicators[$iteration]['realizations_dec'] = $dec === false ? '-' : (string) $item->realizations[$dec]->value;

            $this->iter++;

            if (!empty($item->childsHorizontalRecursive)) {
                $this->mapping__export__indicators($item->childsHorizontalRecursive, $prefix, false);
            }
        });
    }

    //use repo UserRepository, LevelRepository, UnitRepository, IndicatorRepository, RealizationRepository
    public function update(RealizationPaperWorkUpdateRequest $realizationPaperWorkRequest): void
    {
        $userId = $realizationPaperWorkRequest->userId;
        $indicators = $realizationPaperWorkRequest->indicators;
        $realizations = $realizationPaperWorkRequest->realizations;
        $level = $realizationPaperWorkRequest->level;
        $unit = $realizationPaperWorkRequest->unit;
        $year = $realizationPaperWorkRequest->year;

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

                        //update hanya jika value-nya berubah atau (bulan < bulan sekarang -1 dan value = 0 dan masih default)
                        if (($realization->value != $realizations[$indicator->id][$month]) || ($this->monthName__to__monthNumber($month) <= now()->month-1 && $realizations[$indicator->id][$month] == 0 && $realization->default === true)) {
                            $this->realizationRepository->update__value_default__by__month_indicatorId($month, $indicator->id, $realizations[$indicator->id][$month]);
                        }
                    } else {

                        //update hanya jika (sama dengan bulan sekarang -1 dan sama dengan tahun sekarang) atau (sudah di un-lock)
                        if (($this->monthName__to__monthNumber($month) === now()->month-1 && $year == now()->year) || !$this->realizationRepository->find__by__indicatorId_month($indicator->id, $month)->locked) {
                            $this->realizationRepository->update__value_default__by__month_indicatorId($month, $indicator->id, $realizations[$indicator->id][$month]);
                        }
                    }
                }
                //end section: paper work 'CHILD' updating ----------------------------------------------------------------------
            }
        });
    }

    //use repo UserRepository, LevelRepository, UnitRepository, IndicatorRepository, RealizationRepository
    public function update_import(RealizationPaperWorkUpdateRequest $realizationPaperWorkRequest): void
    {
        $indicatorsId = $realizationPaperWorkRequest->indicators;
        $realizations = $realizationPaperWorkRequest->realizations;
        $level = $realizationPaperWorkRequest->level;
        $unit = $realizationPaperWorkRequest->unit;
        $year = $realizationPaperWorkRequest->year;

        //jika user adalah 'super-admin' or 'admin' maka bisa entry realisasi semua bulan, else hanya bisa bulan saat ini or bulan yang un-locked
        DB::transaction(function () use ($indicatorsId, $realizations, $level, $unit, $year) {

            $indicators = $this->indicatorRepository->find__all__by__idList_levelId_unitId_year($indicatorsId, $this->levelRepository->find__id__by__slug($level), $this->unitRepository->find__id__by__slug($unit), $year);

            foreach ($indicators as $indicator) {

                //hanya update jika tidak dummy
                if (!$indicator->dummy) {
                    //section: paper work 'CHILD' updating ----------------------------------------------------------------------
                    foreach ($indicator->validity as $month => $value) {
                        $realization = $this->realizationRepository->find__by__indicatorId_month($indicator->id, $month);

                        //update hanya jika value-nya berubah || (bulan < bulan sekarang -1 && value = 0 && masih default)
                        if ($realization->value != $realizations[$indicator->id][$month] || ($this->monthName__to__monthNumber($month) <= now()->month-1 && $realizations[$indicator->id][$month] == 0 && $realization->default === true)) {
                            $this->realizationRepository->update__value_default__by__month_indicatorId($month, $indicator->id, $realizations[$indicator->id][$month]);
                        }
                    }
                    //end section: paper work 'CHILD' updating ----------------------------------------------------------------------
                }
            }
        });
    }

    //use repo RealizationRepository
    public function lock_change(RealizationPaperWorkChangeLockRequest $realizationPaperWorkRequest): void
    {
        $indicatorId = $realizationPaperWorkRequest->indicatorId;
        $month = $realizationPaperWorkRequest->month;

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
