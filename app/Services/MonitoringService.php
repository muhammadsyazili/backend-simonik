<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\MonitoringExportRequest;
use App\DTO\MonitoringExportResponse;
use App\DTO\MonitoringMonitoringRequest;
use App\DTO\MonitoringMonitoringResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Database\Eloquent\Collection;

class MonitoringService
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
    public function monitoring(MonitoringMonitoringRequest $monitoringRequest): MonitoringMonitoringResponse
    {
        $response = new MonitoringMonitoringResponse();

        $level = $monitoringRequest->level;
        $unit = $monitoringRequest->unit;
        $year = $monitoringRequest->year;
        $month = $monitoringRequest->month;

        $levelId = $this->levelRepository->find__id__by__slug($level);
        $unitId = $this->unitRepository->find__id__by__slug($unit);

        $indicators = $this->indicatorRepository->find__all__with__childs_targets_realizations__by__levelId_unitId_year($levelId, $unitId, $year);

        $this->iter = 0; //reset iterator
        $this->mapping__monitoring__indicators($indicators, ['r' => 255, 'g' => 255, 'b' => 255]);

        $indicators = $this->monitoring_calc($this->indicators, $month);

        $response->indicators = $indicators;

        return $response;
    }

    private function monitoring_calc(array $indicators, string $month): array
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
                $newIndicators['partials'][$i]['id'] = $item['id'];
                $newIndicators['partials'][$i]['indicator'] = $item['indicator'];
                $newIndicators['partials'][$i]['type'] = $item['type'];
                $newIndicators['partials'][$i]['dummy'] = $item['dummy'];
                $newIndicators['partials'][$i]['reducing_factor'] = $item['reducing_factor'];
                $newIndicators['partials'][$i]['measure'] = is_null($item['measure']) ? '-' : $item['measure'];
                $newIndicators['partials'][$i]['polarity'] = $item['polarity'];
                $newIndicators['partials'][$i]['order'] = $item['order'];
                $newIndicators['partials'][$i]['bg_color'] = $item['bg_color'];
                $newIndicators['partials'][$i]['show_chart'] = !$item['dummy'] && !$item['reducing_factor'] && !in_array($status_symbol, ['+0']) ? true : false;

                $newIndicators['partials'][$i]['achievement']['value']['original'] = $achievement;
                $newIndicators['partials'][$i]['achievement']['value']['showed'] = in_array(gettype($achievement), ['double', 'integer']) ? number_format($achievement, $this->decimals_showed, ',', '.') . '%' : $achievement;
                $newIndicators['partials'][$i]['status'] = $status;
                $newIndicators['partials'][$i]['status_symbol'] = $status_symbol;
                $newIndicators['partials'][$i]['status_color'] = $status_color;
                $newIndicators['partials'][$i]['capping_value_100']['value']['original'] = $capping_value_100;
                $newIndicators['partials'][$i]['capping_value_100']['value']['showed'] = in_array(gettype($capping_value_100), ['double', 'integer']) ? number_format($capping_value_100, $this->decimals_showed, ',', '.') : $capping_value_100;
                $newIndicators['partials'][$i]['capping_value_110']['value']['original'] = $capping_value_110;
                $newIndicators['partials'][$i]['capping_value_110']['value']['showed'] = in_array(gettype($capping_value_110), ['double', 'integer']) ? number_format($capping_value_110, $this->decimals_showed, ',', '.') : $capping_value_110;

                $newIndicators['partials'][$i]['prefix'] = $item['prefix'];

                $selected_weight = '-';
                if (!$item['dummy'] && count($item['weight']) !== 0 && array_key_exists($month, $item['validity'])) {
                    $selected_weight = (float) $item['weight'][$month];
                }
                $newIndicators['partials'][$i]['selected_weight'] = $selected_weight;

                $selected_target = '-';
                if (!$item['dummy'] && !$item['reducing_factor'] && array_key_exists($month, $item['validity'])) {
                    $selected_target = (float) $item['targets'][$month]['value'];
                }
                $newIndicators['partials'][$i]['selected_target']['value']['original'] = $selected_target;
                $newIndicators['partials'][$i]['selected_target']['value']['showed'] = in_array(gettype($selected_target), ['double', 'integer']) ? number_format($selected_target, $this->decimals_showed, ',', '.') : $selected_target;

                $selected_realization = '-';
                if (!$item['dummy'] && array_key_exists($month, $item['validity'])) {
                    $selected_realization = (float) $item['realizations'][$month]['value'];
                }
                $newIndicators['partials'][$i]['selected_realization']['value']['original'] = $selected_realization;
                $newIndicators['partials'][$i]['selected_realization']['value']['showed'] = in_array(gettype($selected_realization), ['double', 'integer']) ? number_format($selected_realization, $this->decimals_showed, ',', '.') : $selected_realization;

                // $newIndicators['partials'][$i]['targets']['jan']['value'] = $item['targets']['jan']['value'];
                // $newIndicators['partials'][$i]['targets']['feb']['value'] = $item['targets']['feb']['value'];
                // $newIndicators['partials'][$i]['targets']['mar']['value'] = $item['targets']['mar']['value'];
                // $newIndicators['partials'][$i]['targets']['apr']['value'] = $item['targets']['apr']['value'];
                // $newIndicators['partials'][$i]['targets']['may']['value'] = $item['targets']['may']['value'];
                // $newIndicators['partials'][$i]['targets']['jun']['value'] = $item['targets']['jun']['value'];
                // $newIndicators['partials'][$i]['targets']['jul']['value'] = $item['targets']['jul']['value'];
                // $newIndicators['partials'][$i]['targets']['aug']['value'] = $item['targets']['aug']['value'];
                // $newIndicators['partials'][$i]['targets']['sep']['value'] = $item['targets']['sep']['value'];
                // $newIndicators['partials'][$i]['targets']['oct']['value'] = $item['targets']['oct']['value'];
                // $newIndicators['partials'][$i]['targets']['nov']['value'] = $item['targets']['nov']['value'];
                // $newIndicators['partials'][$i]['targets']['dec']['value'] = $item['targets']['dec']['value'];

                // $newIndicators['partials'][$i]['realizations']['jan']['value'] = $item['realizations']['jan']['value'];
                // $newIndicators['partials'][$i]['realizations']['feb']['value'] = $item['realizations']['feb']['value'];
                // $newIndicators['partials'][$i]['realizations']['mar']['value'] = $item['realizations']['mar']['value'];
                // $newIndicators['partials'][$i]['realizations']['apr']['value'] = $item['realizations']['apr']['value'];
                // $newIndicators['partials'][$i]['realizations']['may']['value'] = $item['realizations']['may']['value'];
                // $newIndicators['partials'][$i]['realizations']['jun']['value'] = $item['realizations']['jun']['value'];
                // $newIndicators['partials'][$i]['realizations']['jul']['value'] = $item['realizations']['jul']['value'];
                // $newIndicators['partials'][$i]['realizations']['aug']['value'] = $item['realizations']['aug']['value'];
                // $newIndicators['partials'][$i]['realizations']['sep']['value'] = $item['realizations']['sep']['value'];
                // $newIndicators['partials'][$i]['realizations']['oct']['value'] = $item['realizations']['oct']['value'];
                // $newIndicators['partials'][$i]['realizations']['nov']['value'] = $item['realizations']['nov']['value'];
                // $newIndicators['partials'][$i]['realizations']['dec']['value'] = $item['realizations']['dec']['value'];

                $i++;
            }

            $newIndicators['total']['KPI_100']['value']['original'] = $total_KPI_100;
            $newIndicators['total']['KPI_100']['value']['showed'] = number_format($total_KPI_100, $this->decimals_showed, ',', '.');
            $newIndicators['total']['KPI_110']['value']['original'] = $total_KPI_110;
            $newIndicators['total']['KPI_110']['value']['showed'] = number_format($total_KPI_110, $this->decimals_showed, ',', '.');
            $newIndicators['total']['PI_100']['value']['original'] = $total_PI_100;
            $newIndicators['total']['PI_100']['value']['showed'] = number_format($total_PI_100, $this->decimals_showed, ',', '.');
            $newIndicators['total']['PI_110']['value']['original'] = $total_PI_110;
            $newIndicators['total']['PI_110']['value']['showed'] = number_format($total_PI_110, $this->decimals_showed, ',', '.');

            $PK_100 = $total_KPI_100 + $total_PI_100;
            $PK_110 = $total_KPI_110 + $total_PI_110;

            $newIndicators['total']['PK_100']['value']['original'] = $PK_100;
            $newIndicators['total']['PK_100']['value']['showed'] = number_format($PK_100, $this->decimals_showed, ',', '.');
            $newIndicators['total']['PK_110']['value']['original'] = $PK_110;
            $newIndicators['total']['PK_110']['value']['showed'] = number_format($PK_110, $this->decimals_showed, ',', '.');

            $PPK_100 = ($total_KPI_100 + $total_PI_100) == (float) 0 ? 0 : (($total_KPI_100 + $total_PI_100) / ($total_weight_counted_KPI + $total_weight_counted_PI)) * 100;
            $PPK_110 = ($total_KPI_110 + $total_PI_110) == (float) 0 ? 0 : (($total_KPI_110 + $total_PI_110) / ($total_weight_counted_KPI + $total_weight_counted_PI)) * 100;

            $newIndicators['total']['PPK_100']['value']['original'] = $PPK_100;
            $newIndicators['total']['PPK_100']['value']['showed'] = number_format($PPK_100, $this->decimals_showed, ',', '.');
            $newIndicators['total']['PPK_110']['value']['original'] = $PPK_110;
            $newIndicators['total']['PPK_110']['value']['showed'] = number_format($PPK_110, $this->decimals_showed, ',', '.');

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
            $newIndicators['total']['PPK_100_status'] = $PPK_100_status;
            $newIndicators['total']['PPK_100_color_status'] = $PPK_100_color_status;

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

    private function mapping__monitoring__indicators(Collection $indicators, array $bg_color, string $prefix = null, bool $first = true): void
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
                $this->mapping__monitoring__indicators($item->childsHorizontalRecursive, ['r' => $bg_color['r'] - 15, 'g' => $bg_color['g'] - 15, 'b' => $bg_color['b'] - 15], $prefix, false);
            }
        });
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository
    public function export(MonitoringExportRequest $monitoringRequest): MonitoringExportResponse
    {
        $response = new MonitoringExportResponse();

        $level = $monitoringRequest->level;
        $unit = $monitoringRequest->unit;
        $year = $monitoringRequest->year;
        $month = $monitoringRequest->month;

        $levelId = $this->levelRepository->find__id__by__slug($level);
        $unitId = $this->unitRepository->find__id__by__slug($unit);

        $indicators = $this->indicatorRepository->find__all__with__childs_targets_realizations__by__levelId_unitId_year($levelId, $unitId, $year);

        $this->iter = 0; //reset iterator
        $this->mapping__export__indicators($indicators);

        $indicators = $this->export_calc($this->indicators, $month);

        $response->indicators = $indicators;

        return $response;
    }

    private function export_calc(array $indicators, string $month): array
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

                //perhitungan status
                $status = '-';
                if (!$item['dummy'] && !$item['reducing_factor'] && array_key_exists($month, $item['validity'])) {
                    if ($item['targets'][$month]['value'] == (float) 0) {
                        $status = 'BELUM DINILAI';
                    } else if ($achievement >= (float) 100) {
                        $status = 'BAIK';
                    } else if ($achievement >= (float) 95 && $achievement < (float) 100) {
                        $status = 'HATI-HATI';
                    } else if ($achievement < (float) 95) {
                        $status = 'MASALAH';
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
                            if (in_array($capping_value_100, ['-', 'BELUM DINILAI'])) {
                                $total_PI_100 += $capping_value_100;
                            }

                            if (in_array($capping_value_110, ['-', 'BELUM DINILAI'])) {
                                $total_PI_110 += $capping_value_110;
                                $total_weight_counted_PI += $item['weight'][$month];
                            }

                            $total_weight_PI += $item['weight'][$month];
                        }
                    }
                }

                //packaging
                $newIndicators[$i]['order'] = (string) $item['order'] + 1;
                $newIndicators[$i]['indicator'] = (string) $item['indicator'];
                $newIndicators[$i]['type'] = (string) $item['type'];
                $newIndicators[$i]['formula'] = (string) is_null($item['formula']) ? '-' : $item['formula'];
                $newIndicators[$i]['measure'] = (string) is_null($item['measure']) ? '-' : $item['measure'];
                $newIndicators[$i]['polarity'] = (string) is_null($item['original_polarity']) ? '-' : ($item['original_polarity'] == '1' ? 'Positif' : 'Nagatif');
                $newIndicators[$i]['weight'] = (string) array_key_exists($month, $item['weight']) ? $item['weight'][$month] : '-';
                $newIndicators[$i]['weight_counted'] = (string) (in_array($capping_value_110, ['-', 'BELUM DINILAI']) ? 0 : (array_key_exists($month, $item['weight']) ? $item['weight'][$month] : '-'));
                $newIndicators[$i]['target'] = (string) is_null($item['targets'][$month]['value']) ? '-' : $item['targets'][$month]['value'];
                $newIndicators[$i]['realization'] = (string) is_null($item['realizations'][$month]['value']) ? '-' : $item['realizations'][$month]['value'];
                $newIndicators[$i]['achievement'] = (string) $achievement;
                $newIndicators[$i]['capping_value_100'] = (string) $capping_value_100;
                $newIndicators[$i]['capping_value_110'] = (string) $capping_value_110;
                $newIndicators[$i]['status'] = (string) $status;

                $newIndicators[$i]['target_jan'] = (string) $item['targets']['jan']['value'];
                $newIndicators[$i]['target_feb'] = (string) $item['targets']['feb']['value'];
                $newIndicators[$i]['target_mar'] = (string) $item['targets']['mar']['value'];
                $newIndicators[$i]['target_apr'] = (string) $item['targets']['apr']['value'];
                $newIndicators[$i]['target_may'] = (string) $item['targets']['may']['value'];
                $newIndicators[$i]['target_jun'] = (string) $item['targets']['jun']['value'];
                $newIndicators[$i]['target_jul'] = (string) $item['targets']['jul']['value'];
                $newIndicators[$i]['target_aug'] = (string) $item['targets']['aug']['value'];
                $newIndicators[$i]['target_sep'] = (string) $item['targets']['sep']['value'];
                $newIndicators[$i]['target_oct'] = (string) $item['targets']['oct']['value'];
                $newIndicators[$i]['target_nov'] = (string) $item['targets']['nov']['value'];
                $newIndicators[$i]['target_dec'] = (string) $item['targets']['dec']['value'];

                $newIndicators[$i]['realization_jan'] = (string) $item['realizations']['jan']['value'];
                $newIndicators[$i]['realization_feb'] = (string) $item['realizations']['feb']['value'];
                $newIndicators[$i]['realization_mar'] = (string) $item['realizations']['mar']['value'];
                $newIndicators[$i]['realization_apr'] = (string) $item['realizations']['apr']['value'];
                $newIndicators[$i]['realization_may'] = (string) $item['realizations']['may']['value'];
                $newIndicators[$i]['realization_jun'] = (string) $item['realizations']['jun']['value'];
                $newIndicators[$i]['realization_jul'] = (string) $item['realizations']['jul']['value'];
                $newIndicators[$i]['realization_aug'] = (string) $item['realizations']['aug']['value'];
                $newIndicators[$i]['realization_sep'] = (string) $item['realizations']['sep']['value'];
                $newIndicators[$i]['realization_oct'] = (string) $item['realizations']['oct']['value'];
                $newIndicators[$i]['realization_nov'] = (string) $item['realizations']['nov']['value'];
                $newIndicators[$i]['realization_dec'] = (string) $item['realizations']['dec']['value'];

                $i++;
            }

            $i = count($newIndicators);
            $newIndicators[$i]['order'] = (string) $i + 1;
            $newIndicators[$i]['indicator'] = 'TOTAL';
            $newIndicators[$i]['type'] = '-';
            $newIndicators[$i]['formula'] = '-';
            $newIndicators[$i]['measure'] = '-';
            $newIndicators[$i]['polarity'] = '-';
            $newIndicators[$i]['weight'] = (string) ($total_weight_KPI + $total_weight_PI);
            $newIndicators[$i]['weight_counted'] = (string) ($total_weight_counted_KPI + $total_weight_counted_PI);
            $newIndicators[$i]['target'] = '-';
            $newIndicators[$i]['realization'] = '-';
            $newIndicators[$i]['achievement'] = '-';
            $newIndicators[$i]['capping_value_100'] = (string) ($total_KPI_100 + $total_PI_100);
            $newIndicators[$i]['capping_value_110'] = (string) ($total_KPI_110 + $total_PI_110);
            $newIndicators[$i]['status'] = '-';

            $newIndicators[$i]['target_jan'] = '-';
            $newIndicators[$i]['target_feb'] = '-';
            $newIndicators[$i]['target_mar'] = '-';
            $newIndicators[$i]['target_apr'] = '-';
            $newIndicators[$i]['target_may'] = '-';
            $newIndicators[$i]['target_jun'] = '-';
            $newIndicators[$i]['target_jul'] = '-';
            $newIndicators[$i]['target_aug'] = '-';
            $newIndicators[$i]['target_sep'] = '-';
            $newIndicators[$i]['target_oct'] = '-';
            $newIndicators[$i]['target_nov'] = '-';
            $newIndicators[$i]['target_dec'] = '-';

            $newIndicators[$i]['realization_jan'] = '-';
            $newIndicators[$i]['realization_feb'] = '-';
            $newIndicators[$i]['realization_mar'] = '-';
            $newIndicators[$i]['realization_apr'] = '-';
            $newIndicators[$i]['realization_may'] = '-';
            $newIndicators[$i]['realization_jun'] = '-';
            $newIndicators[$i]['realization_jul'] = '-';
            $newIndicators[$i]['realization_aug'] = '-';
            $newIndicators[$i]['realization_sep'] = '-';
            $newIndicators[$i]['realization_oct'] = '-';
            $newIndicators[$i]['realization_nov'] = '-';
            $newIndicators[$i]['realization_dec'] = '-';

            $i++;
            $newIndicators[$i]['order'] = (string) $i + 1;
            $newIndicators[$i]['indicator'] = 'NILAI KINERJA ORGANISASI (NKO)';
            $newIndicators[$i]['type'] = '-';
            $newIndicators[$i]['formula'] = '-';
            $newIndicators[$i]['measure'] = '-';
            $newIndicators[$i]['polarity'] = '-';
            $newIndicators[$i]['weight'] = (string) ($total_weight_KPI + $total_weight_PI);
            $newIndicators[$i]['weight_counted'] = (string) ($total_weight_counted_KPI + $total_weight_counted_PI);
            $newIndicators[$i]['target'] = '-';
            $newIndicators[$i]['realization'] = '-';
            $newIndicators[$i]['achievement'] = '-';
            $newIndicators[$i]['capping_value_100'] = (string) (($total_KPI_100 + $total_PI_100) == (float) 0 ? 0 : (($total_KPI_100 + $total_PI_100) / ($total_weight_counted_KPI + $total_weight_counted_PI)) * 100);
            $newIndicators[$i]['capping_value_110'] = (string) (($total_KPI_110 + $total_PI_110) == (float) 0 ? 0 : (($total_KPI_110 + $total_PI_110) / ($total_weight_counted_KPI + $total_weight_counted_PI)) * 100);
            $newIndicators[$i]['status'] = '-';

            $newIndicators[$i]['target_jan'] = '-';
            $newIndicators[$i]['target_feb'] = '-';
            $newIndicators[$i]['target_mar'] = '-';
            $newIndicators[$i]['target_apr'] = '-';
            $newIndicators[$i]['target_may'] = '-';
            $newIndicators[$i]['target_jun'] = '-';
            $newIndicators[$i]['target_jul'] = '-';
            $newIndicators[$i]['target_aug'] = '-';
            $newIndicators[$i]['target_sep'] = '-';
            $newIndicators[$i]['target_oct'] = '-';
            $newIndicators[$i]['target_nov'] = '-';
            $newIndicators[$i]['target_dec'] = '-';

            $newIndicators[$i]['realization_jan'] = '-';
            $newIndicators[$i]['realization_feb'] = '-';
            $newIndicators[$i]['realization_mar'] = '-';
            $newIndicators[$i]['realization_apr'] = '-';
            $newIndicators[$i]['realization_may'] = '-';
            $newIndicators[$i]['realization_jun'] = '-';
            $newIndicators[$i]['realization_jul'] = '-';
            $newIndicators[$i]['realization_aug'] = '-';
            $newIndicators[$i]['realization_sep'] = '-';
            $newIndicators[$i]['realization_oct'] = '-';
            $newIndicators[$i]['realization_nov'] = '-';
            $newIndicators[$i]['realization_dec'] = '-';
        }

        return $newIndicators;
    }

    private function mapping__export__indicators(Collection $indicators, string $prefix = null, bool $first = true): void
    {
        $indicators->each(function ($item, $key) use ($prefix, $first) {
            $prefix = is_null($prefix) ? (string) ($key + 1) : (string) $prefix . '.' . ($key + 1);
            $iteration = $first && $this->iter === 0 ? 0 : $this->iter;
            $indicator = $item->indicator;

            //indikator packaging
            $this->indicators[$iteration]['indicator'] = "$prefix. $indicator";
            $this->indicators[$iteration]['type'] = $item->type;
            $this->indicators[$iteration]['dummy'] = $item->dummy;
            $this->indicators[$iteration]['reducing_factor'] = $item->reducing_factor;
            $this->indicators[$iteration]['original_polarity'] = $item->getRawOriginal('polarity');
            $this->indicators[$iteration]['formula'] = $item->formula;
            $this->indicators[$iteration]['measure'] = $item->measure;
            $this->indicators[$iteration]['weight'] = $item->weight;
            $this->indicators[$iteration]['validity'] = $item->validity;
            $this->indicators[$iteration]['order'] = $iteration;


            //target packaging
            $jan = $item->targets->search(function ($value) {
                return $value->month === 'jan';
            });
            $this->indicators[$iteration]['targets']['jan']['value'] = $jan === false ? '-' : $item->targets[$jan]->value;

            $feb = $item->targets->search(function ($value) {
                return $value->month === 'feb';
            });
            $this->indicators[$iteration]['targets']['feb']['value'] = $feb === false ? '-' : $item->targets[$feb]->value;

            $mar = $item->targets->search(function ($value) {
                return $value->month === 'mar';
            });
            $this->indicators[$iteration]['targets']['mar']['value'] = $mar === false ? '-' : $item->targets[$mar]->value;

            $apr = $item->targets->search(function ($value) {
                return $value->month === 'apr';
            });
            $this->indicators[$iteration]['targets']['apr']['value'] = $apr === false ? '-' : $item->targets[$apr]->value;

            $may = $item->targets->search(function ($value) {
                return $value->month === 'may';
            });
            $this->indicators[$iteration]['targets']['may']['value'] = $may === false ? '-' : $item->targets[$may]->value;

            $jun = $item->targets->search(function ($value) {
                return $value->month === 'jun';
            });
            $this->indicators[$iteration]['targets']['jun']['value'] = $jun === false ? '-' : $item->targets[$jun]->value;

            $jul = $item->targets->search(function ($value) {
                return $value->month === 'jul';
            });
            $this->indicators[$iteration]['targets']['jul']['value'] = $jul === false ? '-' : $item->targets[$jul]->value;

            $aug = $item->targets->search(function ($value) {
                return $value->month === 'aug';
            });
            $this->indicators[$iteration]['targets']['aug']['value'] = $aug === false ? '-' : $item->targets[$aug]->value;

            $sep = $item->targets->search(function ($value) {
                return $value->month === 'sep';
            });
            $this->indicators[$iteration]['targets']['sep']['value'] = $sep === false ? '-' : $item->targets[$sep]->value;

            $oct = $item->targets->search(function ($value) {
                return $value->month === 'oct';
            });
            $this->indicators[$iteration]['targets']['oct']['value'] = $oct === false ? '-' : $item->targets[$oct]->value;

            $nov = $item->targets->search(function ($value) {
                return $value->month === 'nov';
            });
            $this->indicators[$iteration]['targets']['nov']['value'] = $nov === false ? '-' : $item->targets[$nov]->value;

            $dec = $item->targets->search(function ($value) {
                return $value->month === 'dec';
            });
            $this->indicators[$iteration]['targets']['dec']['value'] = $dec === false ? '-' : $item->targets[$dec]->value;

            //realisasi packaging
            $jan = $item->realizations->search(function ($value) {
                return $value->month === 'jan';
            });
            $this->indicators[$iteration]['realizations']['jan']['value'] = $jan === false ? '-' : $item->realizations[$jan]->value;

            $feb = $item->realizations->search(function ($value) {
                return $value->month === 'feb';
            });
            $this->indicators[$iteration]['realizations']['feb']['value'] = $feb === false ? '-' : $item->realizations[$feb]->value;

            $mar = $item->realizations->search(function ($value) {
                return $value->month === 'mar';
            });
            $this->indicators[$iteration]['realizations']['mar']['value'] = $mar === false ? '-' : $item->realizations[$mar]->value;

            $apr = $item->realizations->search(function ($value) {
                return $value->month === 'apr';
            });
            $this->indicators[$iteration]['realizations']['apr']['value'] = $apr === false ? '-' : $item->realizations[$apr]->value;

            $may = $item->realizations->search(function ($value) {
                return $value->month === 'may';
            });
            $this->indicators[$iteration]['realizations']['may']['value'] = $may === false ? '-' : $item->realizations[$may]->value;

            $jun = $item->realizations->search(function ($value) {
                return $value->month === 'jun';
            });
            $this->indicators[$iteration]['realizations']['jun']['value'] = $jun === false ? '-' : $item->realizations[$jun]->value;

            $jul = $item->realizations->search(function ($value) {
                return $value->month === 'jul';
            });
            $this->indicators[$iteration]['realizations']['jul']['value'] = $jul === false ? '-' : $item->realizations[$jul]->value;

            $aug = $item->realizations->search(function ($value) {
                return $value->month === 'aug';
            });
            $this->indicators[$iteration]['realizations']['aug']['value'] = $aug === false ? '-' : $item->realizations[$aug]->value;

            $sep = $item->realizations->search(function ($value) {
                return $value->month === 'sep';
            });
            $this->indicators[$iteration]['realizations']['sep']['value'] = $sep === false ? '-' : $item->realizations[$sep]->value;

            $oct = $item->realizations->search(function ($value) {
                return $value->month === 'oct';
            });
            $this->indicators[$iteration]['realizations']['oct']['value'] = $oct === false ? '-' : $item->realizations[$oct]->value;

            $nov = $item->realizations->search(function ($value) {
                return $value->month === 'nov';
            });
            $this->indicators[$iteration]['realizations']['nov']['value'] = $nov === false ? '-' : $item->realizations[$nov]->value;

            $dec = $item->realizations->search(function ($value) {
                return $value->month === 'dec';
            });
            $this->indicators[$iteration]['realizations']['dec']['value'] = $dec === false ? '-' : $item->realizations[$dec]->value;

            $this->iter++;

            if (!empty($item->childsHorizontalRecursive)) {
                $this->mapping__export__indicators($item->childsHorizontalRecursive, $prefix, false);
            }
        });
    }

    //use repo IndicatorRepository
    public function monitoring_by_id(string|int $id, string $month, string $prefix): array
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
