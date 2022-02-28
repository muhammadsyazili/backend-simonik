<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\IndicatorReferenceUpdateResponse;
use App\DTO\IndicatorReferenceCreateResponse;
use App\DTO\IndicatorReferenceEditRequest;
use App\DTO\IndicatorReferenceUpdateRequest;
use App\DTO\IndicatorReferenceStoreRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class IndicatorReferenceService
{
    private ?IndicatorRepository $indicatorRepository;
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;

    private mixed $indicators = null;
    private mixed $preferences = null;
    private int $iter = 0;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->indicatorRepository = $constructRequest->indicatorRepository;
        $this->levelRepository = $constructRequest->levelRepository;
        $this->unitRepository = $constructRequest->unitRepository;
    }

    //use repo IndicatorRepository
    public function create(): IndicatorReferenceCreateResponse
    {
        $response = new IndicatorReferenceCreateResponse();

        //preferences
        $preferences = $this->indicatorRepository->find__all__with__childs__by__superMasterLabel();

        $this->iter = 0; //reset iterator
        $this->mapping__create__preferences($preferences);

        $preferences_mapped = collect($this->preferences);

        //indicators
        $indicators = $this->indicatorRepository->find__allNotReferenced__by__superMasterLabel();

        $this->iter = 0; //reset iterator
        $this->mapping__create__indicators($indicators, $preferences_mapped, ['r' => 255, 'g' => 255, 'b' => 255]);

        $response->indicators = $this->indicators;

        return $response;
    }

    private function mapping__create__preferences(Collection $preferences, string $prefix = null, bool $first = true): void
    {
        $preferences->each(function ($item, $key) use ($prefix, $first) {
            $prefix = is_null($prefix) ? (string) ($key + 1) : (string) $prefix . '.' . ($key + 1);
            $iteration = $first && $this->iter === 0 ? 0 : $this->iter;

            $indicator = $item->indicator;

            $this->preferences[$iteration]['id'] = $item->id;
            $this->preferences[$iteration]['indicator'] = "$prefix. $indicator";
            $this->preferences[$iteration]['referenced'] = $item->referenced;
            $this->preferences[$iteration]['order'] = $iteration;

            $this->iter++;

            if (!empty($item->childsHorizontalRecursive)) {
                $this->mapping__create__preferences($item->childsHorizontalRecursive, $prefix, false);
            }
        });
    }

    private function mapping__create__indicators(\Illuminate\Database\Eloquent\Collection $indicators, \Illuminate\Support\Collection $preferences, array $bg_color, string $prefix = null, bool $first = true): void
    {
        $indicators->each(function ($indicatorsItem, $indicatorsKey) use ($prefix, $first, $bg_color, $preferences) {
            $prefix = is_null($prefix) ? (string) ($indicatorsKey + 1) : (string) $prefix . '.' . ($indicatorsKey + 1);
            $indicatorsIteration = $first && $this->iter === 0 ? 0 : $this->iter;

            $this->indicators[$indicatorsIteration]['preferences'][0]['id'] = 'root';
            $this->indicators[$indicatorsIteration]['preferences'][0]['indicator'] = '-- INDUK --';
            $this->indicators[$indicatorsIteration]['preferences'][0]['referenced'] = null;
            $this->indicators[$indicatorsIteration]['preferences'][0]['order'] = null;
            $this->indicators[$indicatorsIteration]['preferences'][0]['showed'] = true;
            $this->indicators[$indicatorsIteration]['preferences'][0]['selected'] = is_null($indicatorsItem->parent_horizontal_id) ? true : false;

            $preferences->each(function ($preferencesItem, $preferencesKey) use ($indicatorsIteration, $indicatorsItem) {
                $this->indicators[$indicatorsIteration]['preferences'][$preferencesKey+1]['id'] = $preferencesItem['id'];
                $this->indicators[$indicatorsIteration]['preferences'][$preferencesKey+1]['indicator'] = $preferencesItem['indicator'];
                $this->indicators[$indicatorsIteration]['preferences'][$preferencesKey+1]['referenced'] = $preferencesItem['referenced'];
                $this->indicators[$indicatorsIteration]['preferences'][$preferencesKey+1]['order'] = $preferencesItem['order'];
                $this->indicators[$indicatorsIteration]['preferences'][$preferencesKey+1]['showed'] = $preferencesItem['id'] === $indicatorsItem->id ? false : true;
                $this->indicators[$indicatorsIteration]['preferences'][$preferencesKey+1]['selected'] = $preferencesItem['id'] === $indicatorsItem->parent_horizontal_id ? true : false;
            });

            $indicator = $indicatorsItem->indicator;

            $this->indicators[$indicatorsIteration]['id'] = $indicatorsItem->id;
            $this->indicators[$indicatorsIteration]['indicator'] = "$prefix. $indicator";
            $this->indicators[$indicatorsIteration]['formula'] = $indicatorsItem->formula;
            $this->indicators[$indicatorsIteration]['measure'] = $indicatorsItem->measure;
            $this->indicators[$indicatorsIteration]['weight'] = $indicatorsItem->weight;
            $this->indicators[$indicatorsIteration]['validity'] = $indicatorsItem->validity;
            $this->indicators[$indicatorsIteration]['polarity'] = $indicatorsItem->polarity;
            $this->indicators[$indicatorsIteration]['order'] = $indicatorsIteration;
            $this->indicators[$indicatorsIteration]['bg_color'] = $bg_color;

            $this->iter++;

            if (!empty($indicatorsItem->childsHorizontalRecursive)) {
                $this->mapping__create__indicators($indicatorsItem->childsHorizontalRecursive, $preferences, ['r' => $bg_color['r'] - 15, 'g' => $bg_color['g'] - 15, 'b' => $bg_color['b'] - 15], $prefix, false);
            }
        });
    }

    //use repo IndicatorRepository
    public function store(IndicatorReferenceStoreRequest $indicatorReferenceRequest): void
    {
        $indicators = $indicatorReferenceRequest->indicators;
        $preferences = $indicatorReferenceRequest->preferences;
        DB::transaction(function () use ($indicators, $preferences) {
            for ($i = 0; $i < count($indicators); $i++) {
                $this->indicatorRepository->update__parentHorizontalId_referenced__by__id($indicators[$i], $preferences[$i] === 'root' ? null : $preferences[$i]);
            }
        });
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository
    public function edit(IndicatorReferenceEditRequest $indicatorReferenceRequest): IndicatorReferenceUpdateResponse
    {
        $response = new IndicatorReferenceUpdateResponse();

        $level = $indicatorReferenceRequest->level;
        $unit = $indicatorReferenceRequest->unit;
        $year = $indicatorReferenceRequest->year;

        //preferences
        $preferences = $level === 'super-master' ?
            $this->indicatorRepository->find__allReferenced_rootHorizontal__with__childs__by__label_levelId_unitId_year('super-master', null, null, null) :
            $this->indicatorRepository->find__allReferenced_rootHorizontal__with__childs__by__label_levelId_unitId_year($unit === 'master' ? 'master' : 'child', $this->levelRepository->find__id__by__slug($level), $unit === 'master' ? null : $this->unitRepository->find__id__by__slug($unit), $year);

        $this->iter = 0; //reset iterator
        $this->mapping__edit__preferences($preferences);

        $preferences_mapped = collect($this->preferences);

        //indicators
        $indicators = $level === 'super-master' ?
            $this->indicatorRepository->find__allReferenced_rootHorizontal__with__childs__by__label_levelId_unitId_year('super-master', null, null, null) :
            $this->indicatorRepository->find__allReferenced_rootHorizontal__with__childs__by__label_levelId_unitId_year($unit === 'master' ? 'master' : 'child', $this->levelRepository->find__id__by__slug($level), $unit === 'master' ? null : $this->unitRepository->find__id__by__slug($unit), $year);

        $this->iter = 0; //reset iterator
        $this->mapping__edit__indicators($indicators, $preferences_mapped, ['r' => 255, 'g' => 255, 'b' => 255]);

        $response->indicators = $this->indicators;

        return $response;
    }

    private function mapping__edit__preferences(Collection $preferences, string $prefix = null, bool $first = true): void
    {
        $preferences->each(function ($item, $key) use ($prefix, $first) {
            $prefix = is_null($prefix) ? (string) ($key + 1) : (string) $prefix . '.' . ($key + 1);
            $iteration = $first && $this->iter === 0 ? 0 : $this->iter;

            $indicator = $item->indicator;

            $this->preferences[$iteration]['id'] = $item->id;
            $this->preferences[$iteration]['indicator'] = "$prefix. $indicator";
            $this->preferences[$iteration]['referenced'] = $item->referenced;
            $this->preferences[$iteration]['order'] = $iteration;

            $this->iter++;

            if (!empty($item->childsHorizontalRecursive)) {
                $this->mapping__edit__preferences($item->childsHorizontalRecursive, $prefix, false);
            }
        });
    }

    private function mapping__edit__indicators(\Illuminate\Database\Eloquent\Collection $indicators, \Illuminate\Support\Collection $preferences, array $bg_color, string $prefix = null, bool $first = true): void
    {
        $indicators->each(function ($indicatorsItem, $indicatorsKey) use ($prefix, $first, $bg_color, $preferences) {
            $prefix = is_null($prefix) ? (string) ($indicatorsKey + 1) : (string) $prefix . '.' . ($indicatorsKey + 1);
            $indicatorsIteration = $first && $this->iter === 0 ? 0 : $this->iter;

            $this->indicators[$indicatorsIteration]['preferences'][0]['id'] = 'root';
            $this->indicators[$indicatorsIteration]['preferences'][0]['indicator'] = '-- INDUK --';
            $this->indicators[$indicatorsIteration]['preferences'][0]['referenced'] = null;
            $this->indicators[$indicatorsIteration]['preferences'][0]['order'] = null;
            $this->indicators[$indicatorsIteration]['preferences'][0]['showed'] = true;
            $this->indicators[$indicatorsIteration]['preferences'][0]['selected'] = is_null($indicatorsItem->parent_horizontal_id) ? true : false;

            $preferences->each(function ($preferencesItem, $preferencesKey) use ($indicatorsIteration, $indicatorsItem) {
                $this->indicators[$indicatorsIteration]['preferences'][$preferencesKey+1]['id'] = $preferencesItem['id'];
                $this->indicators[$indicatorsIteration]['preferences'][$preferencesKey+1]['indicator'] = $preferencesItem['indicator'];
                $this->indicators[$indicatorsIteration]['preferences'][$preferencesKey+1]['referenced'] = $preferencesItem['referenced'];
                $this->indicators[$indicatorsIteration]['preferences'][$preferencesKey+1]['order'] = $preferencesItem['order'];
                $this->indicators[$indicatorsIteration]['preferences'][$preferencesKey+1]['showed'] = $preferencesItem['id'] === $indicatorsItem->id ? false : true;
                $this->indicators[$indicatorsIteration]['preferences'][$preferencesKey+1]['selected'] = $preferencesItem['id'] === $indicatorsItem->parent_horizontal_id ? true : false;
            });

            $indicator = $indicatorsItem->indicator;

            $this->indicators[$indicatorsIteration]['id'] = $indicatorsItem->id;
            $this->indicators[$indicatorsIteration]['indicator'] = "$prefix. $indicator";
            $this->indicators[$indicatorsIteration]['formula'] = $indicatorsItem->formula;
            $this->indicators[$indicatorsIteration]['measure'] = $indicatorsItem->measure;
            $this->indicators[$indicatorsIteration]['weight'] = $indicatorsItem->weight;
            $this->indicators[$indicatorsIteration]['validity'] = $indicatorsItem->validity;
            $this->indicators[$indicatorsIteration]['polarity'] = $indicatorsItem->polarity;
            $this->indicators[$indicatorsIteration]['order'] = $indicatorsIteration;
            $this->indicators[$indicatorsIteration]['bg_color'] = $bg_color;

            $this->iter++;

            if (!empty($indicatorsItem->childsHorizontalRecursive)) {
                $this->mapping__edit__indicators($indicatorsItem->childsHorizontalRecursive, $preferences, ['r' => $bg_color['r'] - 15, 'g' => $bg_color['g'] - 15, 'b' => $bg_color['b'] - 15], $prefix, false);
            }
        });
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository
    public function update(IndicatorReferenceUpdateRequest $indicatorReferenceRequest): void
    {
        $indicators = $indicatorReferenceRequest->indicators;
        $preferences = $indicatorReferenceRequest->preferences;
        $level = $indicatorReferenceRequest->level;
        $unit = $indicatorReferenceRequest->unit;
        $year = $indicatorReferenceRequest->year;

        DB::transaction(function () use ($indicators, $preferences, $level, $unit, $year) {
            $indicatorsModel = $level === 'super-master' ? $this->indicatorRepository->find__id_parentHorizontalId__by__label_levelId_unitId_year('super-master', null, null, null) : $this->indicatorRepository->find__id_parentHorizontalId__by__label_levelId_unitId_year($unit === 'master' ? 'master' : 'child', $this->levelRepository->find__id__by__slug($level), $unit === 'master' ? null : $this->unitRepository->find__id__by__slug($unit), $year);

            $indicatorsModel[count($indicatorsModel)] = ['id' => 'root', 'parent_horizontal_id' => 'root']; //sisipan, agar valid jika input-nya 'ROOT'

            if ($level !== 'super-master' && $unit === 'master') {
                //section: paper work current request updating
                $temp = [];
                for ($i = 0; $i < count($indicators); $i++) {
                    $key = array_search($indicators[$i], array_column($indicatorsModel, 'id'));

                    $temp[$i]['id'] = $indicators[$i];
                    $temp[$i]['new_preference'] = $preferences[$i] === 'root' ? null : $preferences[$i];
                    $temp[$i]['old_preference'] = $indicatorsModel[$key]['id'];

                    $this->indicatorRepository->update__parentHorizontalId_referenced__by__id($indicators[$i], $preferences[$i] === 'root' ? null : $preferences[$i]);
                }
                //section end: paper work current request updating

                //section: paper work chlids updating
                $units = $this->unitRepository->find__all__with__indicators__by__levelId_year($this->levelRepository->find__id__by__slug($level), $year);

                foreach ($units as $unit) {
                    foreach ($unit->indicators as $indicator) {
                        $key = array_search($indicator->parent_vertical_id, array_column($temp, 'id'));

                        if ($key !== false) {
                            $this->indicatorRepository->update__parentHorizontalId_referenced__by__id($indicator->id, is_null($temp[$key]['new_preference']) ? null :
                                $this->indicatorRepository->find__id__by__parentVerticalId_levelId_unitId_year($temp[$key]['new_preference'], $this->levelRepository->find__id__by__slug($level), $unit->id, $year));
                        }
                    }
                }
                //end section: paper work chlids updating
            } else {
                for ($i = 0; $i < count($indicators); $i++) {
                    $this->indicatorRepository->update__parentHorizontalId_referenced__by__id($indicators[$i], $preferences[$i] === 'root' ? null : $preferences[$i]);
                }
            }
        });
    }
}
