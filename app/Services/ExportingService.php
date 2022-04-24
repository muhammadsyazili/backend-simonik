<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\ExportingExportingRequest;
use App\DTO\ExportingExportingResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Database\Eloquent\Collection;

class ExportingService
{
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;
    private ?IndicatorRepository $indicatorRepository;

    private array $indicators = [];
    private int $iter = 0;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->levelRepository = $constructRequest->levelRepository;
        $this->unitRepository = $constructRequest->unitRepository;
        $this->indicatorRepository = $constructRequest->indicatorRepository;
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository
    public function exporting(ExportingExportingRequest $exportingRequest): ExportingExportingResponse
    {
        $response = new ExportingExportingResponse();

        $level = $exportingRequest->level;
        $unit = $exportingRequest->unit;
        $year = $exportingRequest->year;
        $month = $exportingRequest->month;

        $levelId = $this->levelRepository->find__id__by__slug($level);
        $unitId = $this->unitRepository->find__id__by__slug($unit);

        $indicators = $this->indicatorRepository->find__all__with__childs_targets_realizations__by__levelId_unitId_year($levelId, $unitId, $year);

        $this->iter = 0; //reset iterator
        $this->mapping__exporting__indicators($indicators);

        $indicators = $this->calculating_exporting($this->indicators, $month);

        $response->indicators = $indicators;

        return $response;
    }

    private function calculating_exporting(array $indicators, string $month): array
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

    private function mapping__exporting__indicators(Collection $indicators, string $prefix = null, bool $first = true): void
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
                $this->mapping__exporting__indicators($item->childsHorizontalRecursive, $prefix, false);
            }
        });
    }
}
