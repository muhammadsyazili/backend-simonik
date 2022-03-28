<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\RangkingRangkingRequest;
use App\DTO\RangkingRangkingResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use Illuminate\Database\Eloquent\Collection;

class RangkingService
{
    private ?LevelRepository $levelRepository;
    private ?IndicatorRepository $indicatorRepository;

    private array $indicators = [];
    private int $iter = 0;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->levelRepository = $constructRequest->levelRepository;
        $this->indicatorRepository = $constructRequest->indicatorRepository;
    }

    //use repo LevelRepository, IndicatorRepository
    public function rangking(RangkingRangkingRequest $rangkingRequest): RangkingRangkingResponse
    {
        $response = new RangkingRangkingResponse();

        $category = $rangkingRequest->category;
        $year = $rangkingRequest->year;
        $month = $rangkingRequest->month;

        $levels = $this->levelRepository->find__all__by__parentId($category);

        $temp = [];
        $i = 0;
        foreach ($levels as $level) {
            foreach ($level->units as $unit) {
                $indicators = $this->indicatorRepository->find__all__with__childs_targets_realizations__by__levelId_year($level->id, $unit->id, $year);

                if (count($indicators) === 0) {
                    $temp[$i]['level_id'] = $level->id;
                    $temp[$i]['name'] = $unit->name;
                    $temp[$i]['value']['original'] = 0;
                    $temp[$i]['value']['showed'] = 0;
                    $temp[$i]['status'] = 'DATA TIDAK TERSEDIA';
                    $temp[$i]['color_status'] = 'light';
                } else {
                    $this->indicators = []; //reset indicators
                    $this->iter = 0; //reset iterator
                    $this->mapping__rangking__indicators($indicators, ['r' => 255, 'g' => 255, 'b' => 255]);

                    $result = $this->rangking_calc($this->indicators, $month);

                    $temp[$i]['level_id'] = $level->id;
                    $temp[$i]['name'] = $unit->name;
                    $temp[$i]['value']['original'] = $result['total']['PPK_110']['value']['original'];
                    $temp[$i]['value']['showed'] = $result['total']['PPK_110']['value']['showed'];
                    $temp[$i]['status'] = $result['total']['PPK_110_status'];
                    $temp[$i]['color_status'] = $result['total']['PPK_110_color_status'];
                }

                $i++;
            }
        }

        $collection = collect($temp);

        $sorted = $collection->sortBy([
            ['value', 'desc'],
            ['level_id', 'asc'],
        ]);

        $response->units = $sorted->values()->all();

        return $response;
    }

    private function rangking_calc(array $indicators, string $month): array
    {
        $newIndicators = [];

        if (count($indicators) !== 0) {
            $total_KPI_100 = 0;
            $total_KPI_110 = 0;
            $total_PI_100 = 0;
            $total_PI_110 = 0;

            $total_weight_counted_KPI = 0;
            $total_weight_counted_PI = 0;

            $total_weight_KPI = 0;
            $total_weight_PI = 0;

            $i = 0;
            foreach ($indicators as $item) {
                //perhitungan pencapaian
                $achievement = '-';
                if (!$item['dummy'] && !$item['reducing_factor'] && array_key_exists($month, $item['validity'])) {
                    if ($item['targets'][$month]['value'] == (float) 0 && $item['realizations'][$month]['value'] == (float) 0) {
                        $achievement = 100;
                    } else if ($item['targets'][$month]['value'] == (float) 0 && $item['realizations'][$month]['value'] !== (float) 0) {
                        $achievement = 0;
                    } else if ($item['original_polarity'] === '1') {
                        $achievement = $item['realizations'][$month]['value'] == (float) 0 ? 0 : ($item['realizations'][$month]['value'] / $item['targets'][$month]['value']) * 100;
                    } else if ($item['original_polarity'] === '-1') {
                        $achievement = $item['realizations'][$month]['value'] == (float) 0 ? 0 : (2 - ($item['realizations'][$month]['value'] / $item['targets'][$month]['value'])) * 100;
                    }
                }

                //perhitungan nilai capping 100%
                $capping_value_100 = '-';
                if (!$item['dummy'] && !$item['reducing_factor'] && array_key_exists($month, $item['validity'])) {
                    if ($item['targets'][$month]['value'] == (float) 0) {
                        $capping_value_100 = 'BELUM DINILAI';
                    } else if ($achievement <= (float) 0) {
                        $capping_value_100 = 0;
                    } else if ($achievement > (float) 0 && $achievement <= (float) 100) {
                        $res = $achievement * $item['weight'][$month];
                        $capping_value_100 = $res == (float) 0 ? 0 : $res / 100;
                    } else {
                        $capping_value_100 = $item['weight'][$month];
                    }
                }

                //perhitungan nilai capping 110%
                $capping_value_110 = '-';
                if (!$item['dummy'] && !$item['reducing_factor'] && array_key_exists($month, $item['validity'])) {
                    if ($item['targets'][$month]['value'] == (float) 0) {
                        $capping_value_110 = 'BELUM DINILAI';
                    } else if ($achievement <= (float) 0) {
                        $capping_value_110 = 0;
                    } else if ($achievement > (float) 0 && $achievement <= (float) 110) {
                        $res = $achievement * $item['weight'][$month];
                        $capping_value_110 = $res == (float) 0 ? 0 : $res / 100;
                    } else {
                        $res = $item['weight'][$month] * 110;
                        $capping_value_110 = $res == (float) 0 ? 0 : $res / 100;
                    }
                }

                //perhitungan total KPI 100% & 110%
                if (strtoupper($item['type']) === 'KPI') {
                    if (array_key_exists($month, $item['validity'])) {
                        if ($item['reducing_factor']) {
                            $total_KPI_100 -= $item['realizations'][$month]['value'];
                            $total_KPI_110 -= $item['realizations'][$month]['value'];
                        } else {
                            if (!in_array($capping_value_100, ['-', 'BELUM DINILAI'])) {
                                $total_KPI_100 += $capping_value_100;
                            }

                            if (!in_array($capping_value_110, ['-', 'BELUM DINILAI'])) {
                                $total_KPI_110 += $capping_value_110;
                                $total_weight_counted_KPI += $item['weight'][$month];
                            }

                            $total_weight_KPI += $item['weight'][$month];
                        }
                    }
                }

                //perhitungan total PI 100% & 110%
                if (strtoupper($item['type']) === 'PI') {
                    if (array_key_exists($month, $item['validity'])) {
                        if ($item['reducing_factor']) {
                            $total_PI_100 -= $item['realizations'][$month]['value'];
                            $total_PI_110 -= $item['realizations'][$month]['value'];
                        } else {
                            if (!in_array($capping_value_100, ['-', 'BELUM DINILAI'])) {
                                $total_PI_100 += $capping_value_100;
                            }

                            if (!in_array($capping_value_110, ['-', 'BELUM DINILAI'])) {
                                $total_PI_110 += $capping_value_110;
                                $total_weight_counted_PI += $item['weight'][$month];
                            }

                            $total_weight_PI += $item['weight'][$month];
                        }
                    }
                }
                $i++;
            }

            $PPK_110 = ($total_KPI_110 + $total_PI_110) == (float) 0 ? 0 : (($total_KPI_110 + $total_PI_110) / ($total_weight_counted_KPI + $total_weight_counted_PI)) * 100;

            $newIndicators['total']['PPK_110']['value']['original'] = $PPK_110;
            $newIndicators['total']['PPK_110']['value']['showed'] = number_format($PPK_110, 2, ',', '.');

            $PPK_110_status = 'MASALAH';
            $PPK_110_color_status = 'danger';
            if ($PPK_110 < 95) {
                $PPK_110_status = 'MASALAH';
                $PPK_110_color_status = 'danger';
            } else if ($PPK_110 >= 95 && $PPK_110 < 100) {
                $PPK_110_status = 'HATI-HATI';
                $PPK_110_color_status = 'warning';
            } else if ($PPK_110 >= 100) {
                $PPK_110_status = 'BAIK';
                $PPK_110_color_status = 'success';
            }
            $newIndicators['total']['PPK_110_status'] = $PPK_110_status;
            $newIndicators['total']['PPK_110_color_status'] = $PPK_110_color_status;
        }

        return $newIndicators;
    }

    private function mapping__rangking__indicators(Collection $indicators, array $bg_color, string $prefix = null, bool $first = true): void
    {
        $indicators->each(function ($item, $key) use ($prefix, $first, $bg_color) {
            $prefix = is_null($prefix) ? (string) ($key + 1) : (string) $prefix . '.' . ($key + 1);
            $iteration = $first && $this->iter === 0 ? 0 : $this->iter;

            $this->indicators[$iteration]['type'] = $item->type;
            $this->indicators[$iteration]['dummy'] = $item->dummy;
            $this->indicators[$iteration]['reducing_factor'] = $item->reducing_factor;
            $this->indicators[$iteration]['weight'] = $item->weight;
            $this->indicators[$iteration]['validity'] = $item->validity;
            $this->indicators[$iteration]['original_polarity'] = $item->getRawOriginal('polarity');

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
                $this->mapping__rangking__indicators($item->childsHorizontalRecursive, ['r' => $bg_color['r'] - 15, 'g' => $bg_color['g'] - 15, 'b' => $bg_color['b'] - 15], $prefix, false);
            }
        });
    }
}
