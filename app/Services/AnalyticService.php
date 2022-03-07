<?php

namespace App\Services;

use App\DTO\AnalyticIndexRequest;
use App\DTO\AnalyticIndexResponse;
use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Database\Eloquent\Collection;

class AnalyticService
{
    private ?LevelRepository $levelRepository;
    private ?IndicatorRepository $indicatorRepository;
    private ?UnitRepository $unitRepository;

    private array $indicators = [];
    private int $iter = 0;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->userRepository = $constructRequest->userRepository;
        $this->levelRepository = $constructRequest->levelRepository;
        $this->indicatorRepository = $constructRequest->indicatorRepository;
        $this->unitRepository = $constructRequest->unitRepository;
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository
    public function analytic(AnalyticIndexRequest $analyticRequest): AnalyticIndexResponse
    {
        $response = new AnalyticIndexResponse();

        $level = $analyticRequest->level;
        $unit = $analyticRequest->unit;
        $year = $analyticRequest->year;
        $month = $analyticRequest->month;
        $userId = $analyticRequest->userId;

        $levelId = $this->levelRepository->find__id__by__slug($level);
        $unitId = $this->unitRepository->find__id__by__slug($unit);

        $indicators = $this->indicatorRepository->find__all__with__childs_targets_realizations__by__levelId_unitId_year($levelId, $unitId, $year);

        $this->iter = 0; //reset iterator
        $this->mapping__index__indicators($indicators, ['r' => 255, 'g' => 255, 'b' => 255]);

        $indicators = $this->calc($this->indicators, $month);

        $response->indicators = $indicators;

        return $response;
    }

    //use repo IndicatorRepository
    public function analytic_by_id(string|int $id, string $month, string $prefix): array
    {
        $indicator = $this->indicatorRepository->find__with__targets_realizations__by__id($id);

        $monthNumber = $this->monthName__to__monthNumber($month);

        $targets = [];
        $realizations = [];
        $months = [];
        for ($i = $monthNumber; $i > 0; $i--) {
            $monthName = $this->monthNumber__to__monthName($i);

            $months[$i] = $monthName;

            $res = $indicator->targets->search(function ($value) use ($monthName) {
                return $value->month === $monthName;
            });
            $targets[$i] = $res === false ? 0 : $indicator->targets[$res]->value;

            $res = $indicator->realizations->search(function ($value) use ($monthName) {
                return $value->month === $monthName;
            });
            $realizations[$i] = $res === false ? 0 : $indicator->realizations[$res]->value;
        }

        $temp = $indicator->indicator;

        $newIndicator = [
            'indicator' => "$prefix. $temp",
            'measure' => $indicator->measure,
            'type' => $indicator->type,
            'targets' => array_reverse($targets),
            'realizations' => array_reverse($realizations),
            'months' => array_reverse($months),
        ];

        return $newIndicator;
    }

    private function calc(array $indicators, string $month): array
    {
        $newIndicators = [];

        $total_KPI_100 = 0;
        $total_KPI_110 = 0;
        $total_PI_100 = 0;
        $total_PI_110 = 0;

        $i = 0;
        foreach ($indicators as $item) {
            //perhitungan pencapaian
            $achievement = 0;
            if (!$item['dummy'] && !$item['reducing_factor']) {
                if ($item['targets'][$month]['value'] === (float) 0 && $item['realizations'][$month]['value'] === (float) 0) {
                    $achievement = 100;
                } else if ($item['targets'][$month]['value'] === (float) 0 && $item['realizations'][$month]['value'] !== (float) 0) {
                    $achievement = 0;
                } else if ($item['original_polarity'] === '1') {
                    $achievement = $item['realizations'][$month]['value'] === (float) 0 ? 0 : ($item['realizations'][$month]['value'] / $item['targets'][$month]['value']) * 100;
                } else if ($item['original_polarity'] === '-1') {
                    $achievement = $item['realizations'][$month]['value'] === (float) 0 ? 0 : (2 - ($item['realizations'][$month]['value'] / $item['targets'][$month]['value'])) * 100;
                } else {
                    $achievement = null;
                }
            } else {
                $achievement = null;
            }

            //perhitungan nilai capping 100%
            $capping_value_100 = null;
            if (!$item['dummy'] && !$item['reducing_factor'] && array_key_exists($month, $item['weight'])) {
                if ($item['targets'][$month]['value'] === (float) 0) {
                    $capping_value_100 = 'BELUM DINILAI';
                } else if ($achievement <= (float) 0) {
                    $capping_value_100 = 0;
                } else if ($achievement > (float) 0 && $achievement <= (float) 100) {
                    $res = $achievement * $item['weight'][$month];
                    $capping_value_100 = $res === (float) 0 ? 0 : $res / 100;
                } else {
                    $capping_value_100 = $item['weight'][$month];
                }
            }

            //perhitungan nilai capping 110%
            $capping_value_110 = null;
            if (!$item['dummy'] && !$item['reducing_factor'] && array_key_exists($month, $item['weight'])) {
                if ($item['targets'][$month]['value'] === (float) 0) {
                    $capping_value_110 = 'BELUM DINILAI';
                } else if ($achievement <= (float) 0) {
                    $capping_value_110 = 0;
                } else if ($achievement > (float) 0 && $achievement <= (float) 110) {
                    $res = $achievement * $item['weight'][$month];
                    $capping_value_110 = $res === (float) 0 ? 0 : $res / 100;
                } else {
                    $res = $item['weight'][$month] * 110;
                    $capping_value_110 = $res === (float) 0 ? 0 : $res / 100;
                }
            }

            //perhitungan status
            $status = null;
            $status_color = null;
            if (!$item['dummy'] && !$item['reducing_factor'] && array_key_exists($month, $item['weight'])) {
                if ($item['targets'][$month]['value'] === (float) 0) {
                    $status = 'BELUM DINILAI';
                    $status_color = 'info';
                } else if ($achievement >= (float) 100) {
                    $status = 'BAIK';
                    $status_color = 'success';
                } else if ($achievement >= (float) 95 && $achievement < (float) 100) {
                    $status = 'HATI-HATI';
                    $status_color = 'warning';
                } else if ($achievement < (float) 95) {
                    $status = 'MASALAH';
                    $status_color = 'danger';
                }
            }

            if (strtoupper($item['type']) === 'KPI') {
                if ($item['reducing_factor']) {
                    $total_KPI_110 -= $item['realizations'][$month]['value'];
                } else {
                    $total_KPI_100 += $capping_value_100 === 'BELUM DINILAI' ? 0 : $capping_value_100;
                    $total_KPI_110 += $capping_value_110 === 'BELUM DINILAI' ? 0 : $capping_value_110;
                }
            }

            if (strtoupper($item['type']) === 'PI') {
                if ($item['reducing_factor']) {
                    $total_PI_110 -= $item['realizations'][$month]['value'];
                } else {
                    $total_PI_100 += $capping_value_100 === 'BELUM DINILAI' ? 0 : $capping_value_100;
                    $total_PI_110 += $capping_value_110 === 'BELUM DINILAI' ? 0 : $capping_value_110;
                }
            }

            //packaging
            $newIndicators['partials'][$i]['id'] = $item['id'];
            $newIndicators['partials'][$i]['indicator'] = $item['indicator'];
            $newIndicators['partials'][$i]['type'] = $item['type'];
            $newIndicators['partials'][$i]['formula'] = is_null($item['formula']) ? '-' : $item['formula'];
            $newIndicators['partials'][$i]['measure'] = is_null($item['measure']) ? '-' : $item['measure'];
            $newIndicators['partials'][$i]['weight'] = $item['weight'];
            $newIndicators['partials'][$i]['validity'] = $item['validity'];
            $newIndicators['partials'][$i]['polarity'] = $item['polarity'];
            $newIndicators['partials'][$i]['order'] = $item['order'];
            $newIndicators['partials'][$i]['bg_color'] = $item['bg_color'];
            $newIndicators['partials'][$i]['achievement'] = $achievement === null ? null : $achievement;
            $newIndicators['partials'][$i]['capping_value_110'] = $capping_value_110;
            $newIndicators['partials'][$i]['capping_value_100'] = $capping_value_100;
            $newIndicators['partials'][$i]['status'] = $status;
            $newIndicators['partials'][$i]['status_color'] = $status_color;

            $newIndicators['partials'][$i]['prefix'] = $item['prefix'];
            $newIndicators['partials'][$i]['month_selected'] = $month;

            $newIndicators['partials'][$i]['targets']['jan']['value'] = $item['targets']['jan']['value'];
            $newIndicators['partials'][$i]['targets']['feb']['value'] = $item['targets']['feb']['value'];
            $newIndicators['partials'][$i]['targets']['mar']['value'] = $item['targets']['mar']['value'];
            $newIndicators['partials'][$i]['targets']['apr']['value'] = $item['targets']['apr']['value'];
            $newIndicators['partials'][$i]['targets']['may']['value'] = $item['targets']['may']['value'];
            $newIndicators['partials'][$i]['targets']['jun']['value'] = $item['targets']['jun']['value'];
            $newIndicators['partials'][$i]['targets']['jul']['value'] = $item['targets']['jul']['value'];
            $newIndicators['partials'][$i]['targets']['aug']['value'] = $item['targets']['aug']['value'];
            $newIndicators['partials'][$i]['targets']['sep']['value'] = $item['targets']['sep']['value'];
            $newIndicators['partials'][$i]['targets']['oct']['value'] = $item['targets']['oct']['value'];
            $newIndicators['partials'][$i]['targets']['nov']['value'] = $item['targets']['nov']['value'];
            $newIndicators['partials'][$i]['targets']['dec']['value'] = $item['targets']['dec']['value'];

            $newIndicators['partials'][$i]['realizations']['jan']['value'] = $item['realizations']['jan']['value'];
            $newIndicators['partials'][$i]['realizations']['feb']['value'] = $item['realizations']['feb']['value'];
            $newIndicators['partials'][$i]['realizations']['mar']['value'] = $item['realizations']['mar']['value'];
            $newIndicators['partials'][$i]['realizations']['apr']['value'] = $item['realizations']['apr']['value'];
            $newIndicators['partials'][$i]['realizations']['may']['value'] = $item['realizations']['may']['value'];
            $newIndicators['partials'][$i]['realizations']['jun']['value'] = $item['realizations']['jun']['value'];
            $newIndicators['partials'][$i]['realizations']['jul']['value'] = $item['realizations']['jul']['value'];
            $newIndicators['partials'][$i]['realizations']['aug']['value'] = $item['realizations']['aug']['value'];
            $newIndicators['partials'][$i]['realizations']['sep']['value'] = $item['realizations']['sep']['value'];
            $newIndicators['partials'][$i]['realizations']['oct']['value'] = $item['realizations']['oct']['value'];
            $newIndicators['partials'][$i]['realizations']['nov']['value'] = $item['realizations']['nov']['value'];
            $newIndicators['partials'][$i]['realizations']['dec']['value'] = $item['realizations']['dec']['value'];

            $i++;
        }

        $newIndicators['total']['KPI_100'] = $total_KPI_100;
        $newIndicators['total']['KPI_110'] = $total_KPI_110;
        $newIndicators['total']['PI_100'] = $total_PI_100;
        $newIndicators['total']['PI_110'] = $total_PI_110;

        return $newIndicators;
    }

    private function mapping__index__indicators(Collection $indicators, array $bg_color, string $prefix = null, bool $first = true): void
    {
        $indicators->each(function ($item, $key) use ($prefix, $first, $bg_color) {
            $prefix = is_null($prefix) ? (string) ($key + 1) : (string) $prefix . '.' . ($key + 1);
            $iteration = $first && $this->iter === 0 ? 0 : $this->iter;
            $indicator = $item->indicator;

            //indikator packaging
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
            $this->indicators[$iteration]['prefix'] = $prefix;

            $this->indicators[$iteration]['original_polarity'] = $item->getRawOriginal('polarity');
            $this->indicators[$iteration]['dummy'] = $item->dummy;
            $this->indicators[$iteration]['reducing_factor'] = $item->reducing_factor;

            //target packaging
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

            //realisasi packaging
            $jan = $item->realizations->search(function ($value) {
                return $value->month === 'jan';
            });
            $this->indicators[$iteration]['realizations']['jan']['value'] = $jan === false ? null : $item->realizations[$jan]->value;

            $feb = $item->realizations->search(function ($value) {
                return $value->month === 'feb';
            });
            $this->indicators[$iteration]['realizations']['feb']['value'] = $feb === false ? null : $item->realizations[$feb]->value;

            $mar = $item->realizations->search(function ($value) {
                return $value->month === 'mar';
            });
            $this->indicators[$iteration]['realizations']['mar']['value'] = $mar === false ? null : $item->realizations[$mar]->value;

            $apr = $item->realizations->search(function ($value) {
                return $value->month === 'apr';
            });
            $this->indicators[$iteration]['realizations']['apr']['value'] = $apr === false ? null : $item->realizations[$apr]->value;

            $may = $item->realizations->search(function ($value) {
                return $value->month === 'may';
            });
            $this->indicators[$iteration]['realizations']['may']['value'] = $may === false ? null : $item->realizations[$may]->value;

            $jun = $item->realizations->search(function ($value) {
                return $value->month === 'jun';
            });
            $this->indicators[$iteration]['realizations']['jun']['value'] = $jun === false ? null : $item->realizations[$jun]->value;

            $jul = $item->realizations->search(function ($value) {
                return $value->month === 'jul';
            });
            $this->indicators[$iteration]['realizations']['jul']['value'] = $jul === false ? null : $item->realizations[$jul]->value;

            $aug = $item->realizations->search(function ($value) {
                return $value->month === 'aug';
            });
            $this->indicators[$iteration]['realizations']['aug']['value'] = $aug === false ? null : $item->realizations[$aug]->value;

            $sep = $item->realizations->search(function ($value) {
                return $value->month === 'sep';
            });
            $this->indicators[$iteration]['realizations']['sep']['value'] = $sep === false ? null : $item->realizations[$sep]->value;

            $oct = $item->realizations->search(function ($value) {
                return $value->month === 'oct';
            });
            $this->indicators[$iteration]['realizations']['oct']['value'] = $oct === false ? null : $item->realizations[$oct]->value;

            $nov = $item->realizations->search(function ($value) {
                return $value->month === 'nov';
            });
            $this->indicators[$iteration]['realizations']['nov']['value'] = $nov === false ? null : $item->realizations[$nov]->value;

            $dec = $item->realizations->search(function ($value) {
                return $value->month === 'dec';
            });
            $this->indicators[$iteration]['realizations']['dec']['value'] = $dec === false ? null : $item->realizations[$dec]->value;

            $this->iter++;

            if (!empty($item->childsHorizontalRecursive)) {
                $this->mapping__index__indicators($item->childsHorizontalRecursive, ['r' => $bg_color['r'] - 15, 'g' => $bg_color['g'] - 15, 'b' => $bg_color['b'] - 15], $prefix, false);
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

    private function monthNumber__to__monthName(int $monthNumber): string
    {
        $monthName = '';

        switch ($monthNumber) {
            case 1:
                $monthName = "jan";
                break;
            case 2:
                $monthName = "feb";
                break;
            case 3:
                $monthName = "mar";
                break;
            case 4:
                $monthName = "apr";
                break;
            case 5:
                $monthName = "may";
                break;
            case 6:
                $monthName = "jun";
                break;
            case 7:
                $monthName = "jul";
                break;
            case 8:
                $monthName = "aug";
                break;
            case 9:
                $monthName = "sep";
                break;
            case 10:
                $monthName = "oct";
                break;
            case 11:
                $monthName = "nov";
                break;
            case 12:
                $monthName = "dec";
                break;
        }

        return $monthName;
    }
}
