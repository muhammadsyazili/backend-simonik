<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\DashboardDashboardRequest;
use App\DTO\DashboardDashboardResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Database\Eloquent\Collection;

class DashboardService
{
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;
    private ?IndicatorRepository $indicatorRepository;

    private array $indicators = [];
    private int $iter = 0;

    private int $decimals_showed = 2;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->levelRepository = $constructRequest->levelRepository;
        $this->unitRepository = $constructRequest->unitRepository;
        $this->indicatorRepository = $constructRequest->indicatorRepository;
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository
    public function dashboard(DashboardDashboardRequest $dashboardRequest): DashboardDashboardResponse
    {
        $response = new DashboardDashboardResponse();

        $level = $dashboardRequest->level;
        $unit = $dashboardRequest->unit;
        $year = $dashboardRequest->year;
        $month = $dashboardRequest->month;

        $levelId = $this->levelRepository->find__id__by__slug($level);
        $unitId = $this->unitRepository->find__id__by__slug($unit);

        $indicators = $this->indicatorRepository->find__all__with__childs_targets_realizations__by__levelId_unitId_year($levelId, $unitId, $year);

        $this->iter = 0; //reset iterator
        $this->mapping__dashboard__indicators($indicators, ['r' => 255, 'g' => 255, 'b' => 255]);

        $indicators = $this->calculating__dashboard($this->indicators, $month);

        $response->indicators = $indicators;

        return $response;
    }

    private function calculating__dashboard(array $indicators, string $month): array
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

                //perhitungan status & warna status
                $status = '-';
                $status_color = 'none';
                $status_symbol = '+0';
                if (!$item['dummy'] && !$item['reducing_factor'] && array_key_exists($month, $item['validity'])) {
                    if ($item['targets'][$month]['value'] == (float) 0) {
                        $status = 'BELUM DINILAI';
                        $status_color = 'info';
                        $status_symbol = '-0';
                    } else if ($achievement >= (float) 100) {
                        $status = 'BAIK';
                        $status_color = 'success';
                        $status_symbol = '+1';
                    } else if ($achievement >= (float) 95 && $achievement < (float) 100) {
                        $status = 'HATI-HATI';
                        $status_color = 'warning';
                        $status_symbol = '0';
                    } else if ($achievement < (float) 95) {
                        $status = 'MASALAH';
                        $status_color = 'danger';
                        $status_symbol = '-1';
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

                //packaging
                $newIndicators['partials'][$i]['indicator'] = $item['indicator'];//
                $newIndicators['partials'][$i]['type'] = $item['type'];//
                $newIndicators['partials'][$i]['measure'] = is_null($item['measure']) ? '-' : $item['measure'];//
                $newIndicators['partials'][$i]['polarity'] = $item['polarity'];//
                $newIndicators['partials'][$i]['bg_color'] = $item['bg_color'];//

                $newIndicators['partials'][$i]['achievement']['value']['original'] = $achievement;//
                $newIndicators['partials'][$i]['achievement']['value']['showed'] = in_array(gettype($achievement), ['double', 'integer']) ? number_format($achievement, $this->decimals_showed, ',', '.') . '%' : $achievement;//
                $newIndicators['partials'][$i]['status'] = $status;//
                $newIndicators['partials'][$i]['status_symbol'] = $status_symbol;//
                $newIndicators['partials'][$i]['status_color'] = $status_color;//

                $i++;
            }

            $PPK_100 = ($total_KPI_100 + $total_PI_100) == (float) 0 ? 0 : (($total_KPI_100 + $total_PI_100) / ($total_weight_counted_KPI + $total_weight_counted_PI)) * 100;
            $PPK_110 = ($total_KPI_110 + $total_PI_110) == (float) 0 ? 0 : (($total_KPI_110 + $total_PI_110) / ($total_weight_counted_KPI + $total_weight_counted_PI)) * 100;

            $newIndicators['total']['PPK_100']['value']['original'] = $PPK_100;//
            $newIndicators['total']['PPK_100']['value']['showed'] = number_format($PPK_100, $this->decimals_showed, ',', '.');//
            $newIndicators['total']['PPK_110']['value']['original'] = $PPK_110;//
            $newIndicators['total']['PPK_110']['value']['showed'] = number_format($PPK_110, $this->decimals_showed, ',', '.');//

            $PPK_100_status = 'MASALAH';
            $PPK_100_color_status = 'danger';
            if ($PPK_100 < 95) {
                $PPK_100_status = 'MASALAH';
                $PPK_100_color_status = 'danger';
            } else if ($PPK_100 >= 95 && $PPK_100 < 100) {
                $PPK_100_status = 'HATI-HATI';
                $PPK_100_color_status = 'warning';
            } else if ($PPK_100 >= 100) {
                $PPK_100_status = 'BAIK';
                $PPK_100_color_status = 'success';
            }
            $newIndicators['total']['PPK_100_status'] = $PPK_100_status;//
            $newIndicators['total']['PPK_100_color_status'] = $PPK_100_color_status;//

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
            $newIndicators['total']['PPK_110_status'] = $PPK_110_status;//
            $newIndicators['total']['PPK_110_color_status'] = $PPK_110_color_status;//
        }

        return $newIndicators;
    }

    private function mapping__dashboard__indicators(Collection $indicators, array $bg_color, string $prefix = null, bool $first = true): void
    {
        $indicators->each(function ($item, $key) use ($prefix, $first, $bg_color) {
            $prefix = is_null($prefix) ? (string) ($key + 1) : (string) $prefix . '.' . ($key + 1);
            $iteration = $first && $this->iter === 0 ? 0 : $this->iter;
            $indicator = $item->indicator;

            //indikator packaging
            $this->indicators[$iteration]['id'] = $item->id;
            $this->indicators[$iteration]['indicator'] = "$prefix. $indicator";
            $this->indicators[$iteration]['type'] = $item->type;
            $this->indicators[$iteration]['dummy'] = $item->dummy;
            $this->indicators[$iteration]['reducing_factor'] = $item->reducing_factor;
            $this->indicators[$iteration]['measure'] = $item->measure;
            $this->indicators[$iteration]['weight'] = $item->weight;
            $this->indicators[$iteration]['validity'] = $item->validity;
            $this->indicators[$iteration]['polarity'] = $item->polarity;
            $this->indicators[$iteration]['original_polarity'] = $item->getRawOriginal('polarity');

            $this->indicators[$iteration]['order'] = $iteration;
            $this->indicators[$iteration]['bg_color'] = $bg_color;
            $this->indicators[$iteration]['prefix'] = $prefix;


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
                $this->mapping__dashboard__indicators($item->childsHorizontalRecursive, ['r' => $bg_color['r'] - 15, 'g' => $bg_color['g'] - 15, 'b' => $bg_color['b'] - 15], $prefix, false);
            }
        });
    }
}
