<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\IndicatorReferenceCreateOrUpdateResponse;
use App\DTO\IndicatorReferenceStoreOrUpdateRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Support\Facades\DB;

class IndicatorReferenceService
{
    private ?IndicatorRepository $indicatorRepository;
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->indicatorRepository = $constructRequest->indicatorRepository;
        $this->levelRepository = $constructRequest->levelRepository;
        $this->unitRepository = $constructRequest->unitRepository;
    }

    //use repo IndicatorRepository
    public function create(): IndicatorReferenceCreateOrUpdateResponse
    {
        $response = new IndicatorReferenceCreateOrUpdateResponse();

        $response->indicators = $this->indicatorRepository->find__allNotReferenced__by__superMasterLabel();
        $response->preferences = $this->indicatorRepository->find__all__with__childs__by__superMasterLabel();

        return $response;
    }

    //use repo IndicatorRepository
    public function store(IndicatorReferenceStoreOrUpdateRequest $indicatorReferenceRequest): void
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
    public function edit(string $level, ?string $unit = null, ?string $year = null): IndicatorReferenceCreateOrUpdateResponse
    {
        $response = new IndicatorReferenceCreateOrUpdateResponse();

        $response->indicators = $level === 'super-master' ?
            $this->indicatorRepository->find__allReferenced_rootHorizontal__with__childs__by__label_levelId_unitId_year('super-master', null, null, null) :
            $this->indicatorRepository->find__allReferenced_rootHorizontal__with__childs__by__label_levelId_unitId_year($unit === 'master' ? 'master' : 'child', $this->levelRepository->find__id__by__slug($level), $unit === 'master' ? null : $this->unitRepository->find__id__by__slug($unit), $year);
        $response->preferences = $level === 'super-master' ?
            $this->indicatorRepository->find__allReferenced_rootHorizontal__with__childs__by__label_levelId_unitId_year('super-master', null, null, null) :
            $this->indicatorRepository->find__allReferenced_rootHorizontal__with__childs__by__label_levelId_unitId_year($unit === 'master' ? 'master' : 'child', $this->levelRepository->find__id__by__slug($level), $unit === 'master' ? null : $this->unitRepository->find__id__by__slug($unit), $year);

        return $response;
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository
    public function update(IndicatorReferenceStoreOrUpdateRequest $indicatorReferenceRequest): void
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
