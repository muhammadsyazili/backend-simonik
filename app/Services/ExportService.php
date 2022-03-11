<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\ExportIndexRequest;
use App\DTO\ExportIndexResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Database\Eloquent\Collection;

class ExportService
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
    public function export(ExportIndexRequest $exportRequest): ExportIndexResponse
    {
        $response = new ExportIndexResponse();

        $level = $exportRequest->level;
        $unit = $exportRequest->unit;
        $year = $exportRequest->year;

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

            //indikator packaging
            $this->indicators[$iteration]['indicator'] = "$prefix. $indicator";
            $this->indicators[$iteration]['type'] = $item->type;
            $this->indicators[$iteration]['formula'] = is_null($item->formula) ? '-' : $item->formula;
            $this->indicators[$iteration]['measure'] = is_null($item->measure) ? '-' : $item->measure;

            $polarity = '-';
            if (is_null($item->getRawOriginal('polarity'))) {
                $polarity = '-';
            } else {
                $polarity = $item->getRawOriginal('polarity') == '1' ? 'Positif' : 'Nagatif';
            }

            $this->indicators[$iteration]['polarity'] = $polarity;

            //target packaging
            $jan = $item->targets->search(function ($value) {
                return $value->month === 'jan';
            });
            $this->indicators[$iteration]['targets_jan'] = $jan === false ? '-' : (string) $item->targets[$jan]->value;

            $feb = $item->targets->search(function ($value) {
                return $value->month === 'feb';
            });
            $this->indicators[$iteration]['targets_feb'] = $feb === false ? '-' : (string) $item->targets[$feb]->value;

            $mar = $item->targets->search(function ($value) {
                return $value->month === 'mar';
            });
            $this->indicators[$iteration]['targets_mar'] = $mar === false ? '-' : (string) $item->targets[$mar]->value;

            $apr = $item->targets->search(function ($value) {
                return $value->month === 'apr';
            });
            $this->indicators[$iteration]['targets_apr'] = $apr === false ? '-' : (string) $item->targets[$apr]->value;

            $may = $item->targets->search(function ($value) {
                return $value->month === 'may';
            });
            $this->indicators[$iteration]['targets_may'] = $may === false ? '-' : (string) $item->targets[$may]->value;

            $jun = $item->targets->search(function ($value) {
                return $value->month === 'jun';
            });
            $this->indicators[$iteration]['targets_jun'] = $jun === false ? '-' : (string) $item->targets[$jun]->value;

            $jul = $item->targets->search(function ($value) {
                return $value->month === 'jul';
            });
            $this->indicators[$iteration]['targets_jul'] = $jul === false ? '-' : (string) $item->targets[$jul]->value;

            $aug = $item->targets->search(function ($value) {
                return $value->month === 'aug';
            });
            $this->indicators[$iteration]['targets_aug'] = $aug === false ? '-' : (string) $item->targets[$aug]->value;

            $sep = $item->targets->search(function ($value) {
                return $value->month === 'sep';
            });
            $this->indicators[$iteration]['targets_sep'] = $sep === false ? '-' : (string) $item->targets[$sep]->value;

            $oct = $item->targets->search(function ($value) {
                return $value->month === 'oct';
            });
            $this->indicators[$iteration]['targets_oct'] = $oct === false ? '-' : (string) $item->targets[$oct]->value;

            $nov = $item->targets->search(function ($value) {
                return $value->month === 'nov';
            });
            $this->indicators[$iteration]['targets_nov'] = $nov === false ? '-' : (string) $item->targets[$nov]->value;

            $dec = $item->targets->search(function ($value) {
                return $value->month === 'dec';
            });
            $this->indicators[$iteration]['targets_dec'] = $dec === false ? '-' : (string) $item->targets[$dec]->value;

            //realisasi packaging
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
}
