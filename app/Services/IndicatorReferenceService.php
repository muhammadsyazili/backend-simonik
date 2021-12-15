<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\IndicatorPreferencesCreateResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Support\Facades\DB;

class IndicatorReferenceService {
    private ?IndicatorRepository $indicatorRepository;
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;

    public function __construct(ConstructRequest $indicatorConstructRequenst)
    {
        $this->indicatorRepository = $indicatorConstructRequenst->indicatorRepository;
        $this->levelRepository = $indicatorConstructRequenst->levelRepository;
        $this->unitRepository = $indicatorConstructRequenst->unitRepository;
    }

    public function create() : IndicatorPreferencesCreateResponse
    {
        $response = new IndicatorPreferencesCreateResponse();
        $response->indicators = $this->indicatorRepository->findAllNotReferencedBySuperMasterLabel();
        $response->preferences = $this->indicatorRepository->findAllWithChildsBySuperMasterLabel();

        return $response;
    }

    public function store(array $indicator, array $preference) : void
    {
        DB::transaction(function () use ($indicator, $preference) {
            for ($i=0; $i < count($indicator); $i++) {
                $this->indicatorRepository->updateReferenceById($indicator[$i], $preference[$i] === 'root' ? null : $preference[$i]);
            }
        });
    }

    public function edit(string $level, ?string $unit = null, ?string $year = null)
    {
        return $this->indicatorRepository->findAllReferencedWithChildsByWhere(
            $level === 'super-master' ?
            ['label' => 'super-master'] :
            [
                'level_id' => $this->levelRepository->findIdBySlug($level),
                'label' => $unit === 'master' ? 'master' : 'child',
                'unit_id' => $unit === 'master' ? null : $this->unitRepository->findIdBySlug($unit),
                'year' => $year,
            ]
        );
    }

    public function update(array $indicators, array $preferences, string $level, ?string $unit = null, ?string $year = null) : void
    {
        $indicatorsModel = $this->indicatorRepository->findIdAndParentHorizontalIdByWhere(
            $level === 'super-master' ?
            ['label' => 'super-master'] :
            [
                'level_id' => $this->levelRepository->findIdBySlug($level),
                'label' => $unit === 'master' ? 'master' : 'child',
                'unit_id' => $unit === 'master' ? null : $this->unitRepository->findIdBySlug($unit),
                'year' => $year,
            ]
        );

        $indicatorsModel[count($indicatorsModel)] = ['id' => 'root', 'parent_horizontal_id' => 'root']; //sisipan, agar valid jika input-nya 'ROOT'

        if ($level !== 'super-master' && $unit === 'master') {
            //section: paper work current request updating
            $temp = [];
            for ($i=0; $i < count($indicators); $i++) {
                $key = array_search($indicators[$i], array_column($indicatorsModel, 'id'));

                $temp[$i]['id'] = $indicators[$i];
                $temp[$i]['new_preference'] = $preferences[$i] === 'root' ? null : $preferences[$i];
                $temp[$i]['old_preference'] = $indicatorsModel[$key]['id'];

                $this->indicatorRepository->updateReferenceById($indicators[$i], $preferences[$i] === 'root' ? null : $preferences[$i]);
            }
            //section end: paper work current request updating

            //section: paper work chlids updating
            $units = $this->unitRepository->findAllWithIndicatorByLevelIdAndYear($this->levelRepository->findIdBySlug($level), $year);

            foreach ($units as $unit) {
                foreach ($unit->indicators as $indicator) {
                    $key = array_search($indicator->parent_vertical_id, array_column($temp, 'id'));

                    if ($key !== false) {
                        $this->indicatorRepository->updateReferenceById($indicator->id, is_null($temp[$key]['new_preference']) ? null :
                        $this->indicatorRepository->findIdByWhere([
                            'level_id' => $this->levelRepository->findIdBySlug($level),
                            'unit_id' => $unit->id,
                            'year' => $year,
                            'parent_vertical_id' => $temp[$key]['new_preference'],
                        ]));
                    }
                }
            }
            //end section: paper work chlids updating
        } else {
            for ($i=0; $i < count($indicators); $i++) {
                $this->indicatorRepository->updateReferenceById($indicators[$i], $preferences[$i] === 'root' ? null : $preferences[$i]);
            }
        }
    }
}
