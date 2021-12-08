<?php

namespace App\Services;

use App\DTO\IndicatorConstructRequest;
use App\DTO\IndicatorPreferencesCreateResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Support\Facades\DB;

class IndicatorReferenceService {
    private IndicatorRepository $indicatorRepository;
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;

    public function __construct(IndicatorConstructRequest $indicatorConstructRequenst)
    {
        $this->indicatorRepository = $indicatorConstructRequenst->indicatorRepository;
        $this->levelRepository = $indicatorConstructRequenst->levelRepository;
        $this->unitRepository = $indicatorConstructRequenst->unitRepository;
    }

    public function create() : IndicatorPreferencesCreateResponse
    {
        $indicators = $this->indicatorRepository->findAllSuperMasterLevelNotReferenced();
        $preferences = $this->indicatorRepository->findAllPreference();

        $response = new IndicatorPreferencesCreateResponse();
        $response->indicators = $indicators;
        $response->preferences = $preferences;

        return $response;
    }

    public function insert(array $indicator, array $preference) : void
    {
        DB::transaction(function () use ($indicator, $preference) {
            for ($i=0; $i < count($indicator); $i++) {
                $this->indicatorRepository->insertReferenceByIndicator($indicator[$i], $preference[$i] === 'root' ? null : $preference[$i]);
            }
        });
    }

    public function edit($level, $unit, $year)
    {
        return $this->indicatorRepository->findAllWithChildByLevelUnitYear(
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
}
