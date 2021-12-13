<?php

namespace App\Services;

use App\Domains\Indicator;
use App\Domains\Realization;
use App\Domains\Target;
use App\DTO\ConstructRequest;
use App\DTO\IndicatorPaperWorkIndexResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\TargetRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class IndicatorPaperWorkService {
    private ?IndicatorRepository $indicatorRepository;
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;
    private ?UserRepository $userRepository;
    private ?TargetRepository $targetRepository;
    private ?RealizationRepository $realizationRepository;

    public function __construct(ConstructRequest $indicatorConstructRequenst)
    {
        $this->indicatorRepository = $indicatorConstructRequenst->indicatorRepository;
        $this->levelRepository = $indicatorConstructRequenst->levelRepository;
        $this->unitRepository = $indicatorConstructRequenst->unitRepository;
        $this->userRepository = $indicatorConstructRequenst->userRepository;
        $this->targetRepository = $indicatorConstructRequenst->targetRepository;
        $this->realizationRepository = $indicatorConstructRequenst->realizationRepository;
    }

    public function index(string|int $userId, string $level, ?string $unit, ?string $year) : IndicatorPaperWorkIndexResponse
    {
        $response = new IndicatorPaperWorkIndexResponse();

        $user = $this->userRepository->findWithRoleUnitLevelById($userId);

        $isSuperAdmin = $user->role->name === 'super-admin';
        $isSuperAdminOrAdmin = $isSuperAdmin || $user->role->name === 'admin';

        // 'permissions paper work indicator (create, edit, delete)' handler
        $numberOfLevel = $isSuperAdmin ? count(Arr::flatten($this->levelRepository->findAllSlugWithChildsByRoot())) : count(Arr::flatten($this->levelRepository->findAllSlugWithChildsById($user->unit->level->id)));

        $response->levels = $isSuperAdmin ? $this->levelRepository->findAllWithChildsByRoot() : $this->levelRepository->findAllWithChildsById($user->unit->level->id);

        $response->indicators = $this->indicatorRepository->findAllReferencedWithChildsByWhere(
            $level === 'super-master' ?
            ['label' => 'super-master'] :
            [
                'level_id' => $this->levelRepository->findIdBySlug($level),
                'label' => $unit === 'master' ? 'master' : 'child',
                'unit_id' => $unit === 'master' ? null : $this->unitRepository->findIdBySlug($unit),
                'year' => $year
            ]
        );

        $response->permissions = [
            'indicator' => [
                'create' => $isSuperAdmin ? true : false,
                'edit' => $isSuperAdmin ? true : false,
                'delete' => $isSuperAdmin ? true : false,
                'changes_order' => $isSuperAdminOrAdmin ? true : false
            ],
            'reference' => [
                'create' => $isSuperAdmin ? true : false,
                'edit' => ($numberOfLevel > 1 && $isSuperAdminOrAdmin) ? true : false,
            ],
            'paper_work' => ['indicator' => [
                'create' => ($numberOfLevel > 1 && $isSuperAdminOrAdmin) ? true : false,
                'edit' => ($numberOfLevel > 1 && $isSuperAdminOrAdmin) ? true : false,
                'delete' => ($numberOfLevel > 1 && $isSuperAdminOrAdmin) ? true : false,
            ]],
        ];

        return $response;
    }

    public function create(string|int $userId) : IndicatorPaperWorkIndexResponse
    {
        $response = new IndicatorPaperWorkIndexResponse();

        $user = $this->userRepository->findWithRoleUnitLevelById($userId);

        $parent_id = $user->role->name === 'super-admin' ? $this->levelRepository->findAllIdByRoot() : $this->levelRepository->findAllIdById($user->unit->level->id);

        $response->levels = $this->levelRepository->findAllWithChildsByParentId(Arr::flatten($parent_id));
        $response->indicators = $this->indicatorRepository->findAllReferencedWithChildsByWhere(['label' => 'super-master']);

        return $response;
    }

    public function store(array $indicators, string $level, string $year, string|int $userId) : void
    {
        $levelId = $this->levelRepository->findIdBySlug($level);

        //cek indikator yang dipilih punya urutan sampai ke ROOT
        $childsOfSelectedIndicator = [];
        foreach ($indicators as $key => $value) {
            $childsOfSelectedIndicator = array_merge($childsOfSelectedIndicator, Arr::flatten($this->indicatorRepository->findAllWithParentsById($value)));
        }

        $paperWorkIndicators = $this->indicatorRepository->findAllById(array_unique($childsOfSelectedIndicator));

        $domainIndicator = new Indicator();
        $target = new Target();
        $realization = new Realization();

        DB::transaction(function () use ($domainIndicator, $target, $realization, $paperWorkIndicators, $levelId, $userId, $year) {
            //section: paper work 'MASTER' creating

            //build ID
            $idListMaster = [];
            foreach ($paperWorkIndicators as $indicator) {
                $idListMaster[$indicator->id] = (string) Str::orderedUuid();
            }

            $i = 0;
            foreach ($paperWorkIndicators as $indicator) {
                $domainIndicator->id = $idListMaster[$indicator->id];
                $domainIndicator->indicator = $indicator->indicator;
                $domainIndicator->formula = $indicator->formula;
                $domainIndicator->measure = $indicator->measure;
                $domainIndicator->weight = $indicator->getRawOriginal('weight');
                $domainIndicator->polarity = $indicator->getRawOriginal('polarity');
                $domainIndicator->year = $year;
                $domainIndicator->reducing_factor = $indicator->getRawOriginal('reducing_factor');
                $domainIndicator->validity = $indicator->getRawOriginal('validity');
                $domainIndicator->reviewed = $indicator->reviewed;
                $domainIndicator->referenced = $indicator->referenced;
                $domainIndicator->dummy = $indicator->dummy;
                $domainIndicator->label = 'master';
                $domainIndicator->unit_id = null;
                $domainIndicator->level_id = $levelId;
                $domainIndicator->order = $i;
                $domainIndicator->code = $indicator->code;
                $domainIndicator->parent_vertical_id = $indicator->id;
                $domainIndicator->parent_horizontal_id = is_null($indicator->parent_horizontal_id) ? null : $idListMaster[$indicator->parent_horizontal_id];
                $domainIndicator->created_by = $userId;

                $this->indicatorRepository->save($domainIndicator);

                //target 'MASTER' creating
                if (!is_null($indicator->validity)) {
                    foreach ($indicator->validity as $key => $value) {
                        $target->id = (string) Str::orderedUuid();
                        $target->indicator_id = $idListMaster[$indicator->id];
                        $target->month = $key;
                        $target->value = 0;
                        $target->locked = 1;
                        $target->default = 1;

                        $this->targetRepository->save($target);
                    }
                }
                $i++;
            }
            //end section: paper work 'MASTER' creating

            //section: paper work 'CHILD' creating
            $units = $this->unitRepository->findAllByLevelId($levelId);
            $paperWorkIndicators = $this->indicatorRepository->findAllByWhere(['level_id' => $levelId, 'label' => 'master', 'year' => $year]);

            foreach ($units as $unit) {
                //build ID
                $idListChild = [];
                foreach ($paperWorkIndicators as $indicator) {
                    $idListChild[$indicator->id] = (string) Str::orderedUuid();
                }

                $i = 0;
                foreach ($paperWorkIndicators as $indicator) {
                    $domainIndicator->id = $idListChild[$indicator->id];
                    $domainIndicator->indicator = $indicator->indicator;
                    $domainIndicator->formula = $indicator->formula;
                    $domainIndicator->measure = $indicator->measure;
                    $domainIndicator->weight = $indicator->getRawOriginal('weight');
                    $domainIndicator->polarity = $indicator->getRawOriginal('polarity');
                    $domainIndicator->year = $year;
                    $domainIndicator->reducing_factor = $indicator->getRawOriginal('reducing_factor');
                    $domainIndicator->validity = $indicator->getRawOriginal('validity');
                    $domainIndicator->reviewed = $indicator->reviewed;
                    $domainIndicator->referenced = $indicator->referenced;
                    $domainIndicator->dummy = $indicator->dummy;
                    $domainIndicator->label = 'master';
                    $domainIndicator->unit_id = $unit->id;
                    $domainIndicator->level_id = $levelId;
                    $domainIndicator->order = $i;
                    $domainIndicator->code = $indicator->code;
                    $domainIndicator->parent_vertical_id = $indicator->id;
                    $domainIndicator->parent_horizontal_id = is_null($indicator->parent_horizontal_id) ? null : $idListChild[$indicator->parent_horizontal_id];
                    $domainIndicator->created_by = $userId;

                    $this->indicatorRepository->save($domainIndicator);

                    //target & realization 'CHILD' creating
                    if (!is_null($indicator->validity)) {
                        foreach ($indicator->validity as $key => $value) {
                            $target->id = (string) Str::orderedUuid();
                            $target->indicator_id = $idListChild[$indicator->id];
                            $target->month = $key;
                            $target->value = 0;
                            $target->locked = 1;
                            $target->default = 1;

                            $this->targetRepository->save($target);

                            $realization->id = (string) Str::orderedUuid();
                            $realization->indicator_id = $idListChild[$indicator->id];
                            $realization->month = $key;
                            $realization->value = 0;
                            $realization->locked = 1;
                            $realization->default = 1;

                            $this->realizationRepository->save($realization);
                        }
                    }
                    $i++;
                }
            }
            //end section: paper work 'CHILD' creating
        });
    }
}
