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

    public function __construct(ConstructRequest $indicatorConstructRequest)
    {
        $this->indicatorRepository = $indicatorConstructRequest->indicatorRepository;
        $this->levelRepository = $indicatorConstructRequest->levelRepository;
        $this->unitRepository = $indicatorConstructRequest->unitRepository;
        $this->userRepository = $indicatorConstructRequest->userRepository;
        $this->targetRepository = $indicatorConstructRequest->targetRepository;
        $this->realizationRepository = $indicatorConstructRequest->realizationRepository;
    }

    public function index(string|int $userId, string $level, ?string $unit, ?string $year) : IndicatorPaperWorkIndexResponse
    {
        $response = new IndicatorPaperWorkIndexResponse();

        $user = $this->userRepository->findWithRoleUnitLevelById($userId);

        $isSuperMaster = $level === 'super-master' ? true : false;
        $isSuperAdmin = $user->role->name === 'super-admin';
        $isSuperAdminOrAdmin = $isSuperAdmin || $user->role->name === 'admin';

        $currentLevelNotSameWithUserLevel = true;
        if ($user->role->name === 'super-admin') {
            $currentLevelNotSameWithUserLevel = true;
        } else {
            if ($level === $user->unit->level->slug) {
                $currentLevelNotSameWithUserLevel = false;
            } else {
                $currentLevelNotSameWithUserLevel = true;
            }
        }

        // 'permissions paper work indicator (create, edit, delete)' handler
        $numberOfChildLevel = $isSuperAdmin ? count(Arr::flatten($this->levelRepository->findAllSlugWithChildsByRoot())) : count(Arr::flatten($this->levelRepository->findAllSlugWithThisAndChildsById($user->unit->level->id)));

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
                'edit' => $isSuperAdminOrAdmin && $currentLevelNotSameWithUserLevel ? true : false,
                'delete' => $isSuperAdmin && $isSuperMaster ? true : false,
                'changes_order' => $isSuperAdminOrAdmin && $currentLevelNotSameWithUserLevel ? true : false
            ],
            'reference' => [
                'create' => $isSuperAdmin ? true : false,
                'edit' => ($numberOfChildLevel > 1) && $isSuperAdminOrAdmin && $currentLevelNotSameWithUserLevel ? true : false,
            ],
            'paper_work' => ['indicator' => [
                'create' => ($numberOfChildLevel > 1) && $isSuperAdminOrAdmin ? true : false,
                'edit' => ($numberOfChildLevel > 1) && $isSuperAdminOrAdmin && $currentLevelNotSameWithUserLevel ? true : false,
                'delete' => ($numberOfChildLevel > 1) && $isSuperAdminOrAdmin && $currentLevelNotSameWithUserLevel ? true : false,
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
                $domainIndicator->reducing_factor = $indicator->reducing_factor;
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
                        $target->locked = true;
                        $target->default = true;

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
                    $domainIndicator->reducing_factor = $indicator->reducing_factor;
                    $domainIndicator->validity = $indicator->getRawOriginal('validity');
                    $domainIndicator->reviewed = $indicator->reviewed;
                    $domainIndicator->referenced = $indicator->referenced;
                    $domainIndicator->dummy = $indicator->dummy;
                    $domainIndicator->label = 'child';
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
                            $target->locked = true;
                            $target->default = true;

                            $this->targetRepository->save($target);

                            $realization->id = (string) Str::orderedUuid();
                            $realization->indicator_id = $idListChild[$indicator->id];
                            $realization->month = $key;
                            $realization->value = 0;
                            $realization->locked = true;
                            $realization->default = true;

                            $this->realizationRepository->save($realization);
                        }
                    }
                    $i++;
                }
            }
            //end section: paper work 'CHILD' creating
        });
    }

    public function destroy(string $level, string $unit, string $year) : void
    {
        $where = $unit === 'master' ? ['level_id' => $this->levelRepository->findIdBySlug($level), 'year' => $year] : ['level_id' => $this->levelRepository->findIdBySlug($level), 'unit_id' => $this->unitRepository->findIdBySlug($unit), 'year' => $year];

        $indicators = $this->indicatorRepository->findAllWithTargetsAndRealizationsByWhere($where);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::transaction(function () use ($indicators, $level, $unit, $year) {
            //deleting target & realization
            foreach ($indicators as $indicator) {
                foreach ($indicator->targets as $target) {
                    $this->targetRepository->deleteById($target->id);
                }

                foreach ($indicator->realizations as $realization) {
                    $this->realizationRepository->deleteById($realization->id);
                }
            }

            //deleting indicator
            $where = $unit === 'master' ? ['level_id' => $this->levelRepository->findIdBySlug($level), 'year' => $year] : ['level_id' => $this->levelRepository->findIdBySlug($level), 'unit_id' => $this->unitRepository->findIdBySlug($unit), 'year' => $year];

            $this->indicatorRepository->deleteByWhere($where);
        });
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
