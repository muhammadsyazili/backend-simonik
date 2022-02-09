<?php

namespace App\Services;

use App\Domains\Indicator;
use App\Domains\Realization;
use App\Domains\Target;
use App\DTO\ConstructRequest;
use App\DTO\IndicatorInsertOrUpdateRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\TargetRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class IndicatorService {
    private ?IndicatorRepository $indicatorRepository;
    private ?LevelRepository $levelRepository;
    private ?TargetRepository $targetRepository;
    private ?RealizationRepository $realizationRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->indicatorRepository = $constructRequest->indicatorRepository;
        $this->levelRepository = $constructRequest->levelRepository;
        $this->targetRepository = $constructRequest->targetRepository;
        $this->realizationRepository = $constructRequest->realizationRepository;
    }

    //use repo IndicatorRepository, LevelRepository
    public function store(IndicatorInsertOrUpdateRequest $indicator) : void
    {
        DB::transaction(function () use ($indicator) {
            $indicatorDomain = new Indicator();

            $toJson = $this->validity_and_weight__to__JSON($indicator->validity, $indicator->weight);

            if ($indicator->dummy === '1') {
                $indicatorDomain->dummy = true;
                $indicatorDomain->reducing_factor = null;
                $indicatorDomain->polarity = null;
                $indicatorDomain->validity = null;
                $indicatorDomain->weight = null;
            } else {
                if ($indicator->reducing_factor === '1') {
                    $indicatorDomain->reducing_factor = true;
                    $indicatorDomain->polarity = null;
                } else {
                    $indicatorDomain->reducing_factor = false;
                    $indicatorDomain->polarity = $indicator->polarity;
                }

                $indicatorDomain->dummy = false;
                $indicatorDomain->validity = $toJson['validity'];
                $indicatorDomain->weight =  $toJson['weight'];
            }

            $id = (string) Str::orderedUuid();

            $indicatorDomain->id = $id;
            $indicatorDomain->indicator = $indicator->indicator;
            $indicatorDomain->formula = $indicator->formula;
            $indicatorDomain->measure = $indicator->measure;
            $indicatorDomain->year = null;
            $indicatorDomain->reviewed = true;
            $indicatorDomain->referenced = false;
            $indicatorDomain->label = 'super-master';
            $indicatorDomain->unit_id = null;
            $indicatorDomain->level_id = $this->levelRepository->find__id__by__slug('super-master');
            $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year('super-master');
            $indicatorDomain->code = null;
            $indicatorDomain->parent_vertical_id = null;
            $indicatorDomain->parent_horizontal_id = null;
            $indicatorDomain->created_by = $indicator->user_id;

            $this->indicatorRepository->save($indicatorDomain);
            $this->indicatorRepository->update__code__by__id($id);
        });
    }

    //use repo IndicatorRepository
    public function edit(string|int $id)
    {
        $indicator = $this->indicatorRepository->find__with__level__by__id($id);
        $indicator->original_polarity = $indicator->getRawOriginal('polarity');
        return $indicator;
    }

    //use repo IndicatorRepository, TargetRepository, RealizationRepository
    public function update(IndicatorInsertOrUpdateRequest $indicatorNew, string|int $id) : void
    {
        DB::transaction(function () use ($indicatorNew, $id) {
            $indicatorDomain = new Indicator();
            $targetDomain = new Target();
            $realizationDomain = new Realization();

            $indicatorOld = $this->indicatorRepository->find__by__id($id);

            if ($indicatorOld->label === 'super-master') {
                //convert (validity & weight) from array to JSON string
                $toJson = $this->validity_and_weight__to__JSON($indicatorNew->validity, $indicatorNew->weight);

                if ($indicatorNew->dummy === '1') {
                    $indicatorDomain->dummy = true;
                    $indicatorDomain->reducing_factor = null;
                    $indicatorDomain->polarity = null;
                    $indicatorDomain->validity = null;
                    $indicatorDomain->weight = null;
                } else {
                    if ($indicatorNew->reducing_factor === '1') {
                        $indicatorDomain->reducing_factor = true;
                        $indicatorDomain->polarity = null;
                    } else {
                        $indicatorDomain->reducing_factor = false;
                        $indicatorDomain->polarity = $indicatorNew->polarity;
                    }

                    $indicatorDomain->dummy = false;
                    $indicatorDomain->validity = $toJson['validity'];
                    $indicatorDomain->weight =  $toJson['weight'];
                }

                $indicatorDomain->indicator = $indicatorNew->indicator;
                $indicatorDomain->formula = $indicatorNew->formula;
                $indicatorDomain->measure = $indicatorNew->measure;
                $indicatorDomain->year = $indicatorOld->year;
                $indicatorDomain->reviewed = $indicatorOld->reviewed;
                $indicatorDomain->referenced = $indicatorOld->referenced;
                $indicatorDomain->label = $indicatorOld->label;
                $indicatorDomain->unit_id = $indicatorOld->unit_id;
                $indicatorDomain->level_id = $indicatorOld->level_id;
                $indicatorDomain->order = $indicatorOld->order;
                $indicatorDomain->parent_vertical_id = $indicatorOld->parent_vertical_id;
                $indicatorDomain->parent_horizontal_id = $indicatorOld->parent_horizontal_id;

                $this->indicatorRepository->update__by__id($indicatorDomain, $id); //update KPI
            } else if ($indicatorOld->label === 'master') {
                /**
                 * section: master
                 */

                //convert (validity & weight) from array to JSON string
                $toJson = $this->validity_and_weight__to__JSON($indicatorNew->validity, $indicatorNew->weight);

                if ($indicatorNew->dummy === '1') {
                    $indicatorDomain->dummy = true;
                    $indicatorDomain->reducing_factor = null;
                    $indicatorDomain->polarity = null;
                    $indicatorDomain->validity = null;
                    $indicatorDomain->weight = null;
                } else {
                    if ($indicatorNew->reducing_factor === '1') {
                        $indicatorDomain->reducing_factor = true;
                        $indicatorDomain->polarity = null;
                    } else {
                        $indicatorDomain->reducing_factor = false;
                        $indicatorDomain->polarity = $indicatorNew->polarity;
                    }

                    $indicatorDomain->dummy = false;
                    $indicatorDomain->validity = $toJson['validity'];
                    $indicatorDomain->weight =  $toJson['weight'];
                }

                $indicatorDomain->indicator = $indicatorNew->indicator;
                $indicatorDomain->formula = $indicatorNew->formula;
                $indicatorDomain->measure = $indicatorNew->measure;
                $indicatorDomain->year = $indicatorOld->year;
                $indicatorDomain->reviewed = $indicatorOld->reviewed;
                $indicatorDomain->referenced = $indicatorOld->referenced;
                $indicatorDomain->label = $indicatorOld->label;
                $indicatorDomain->unit_id = $indicatorOld->unit_id;
                $indicatorDomain->level_id = $indicatorOld->level_id;
                $indicatorDomain->order = $indicatorOld->order;
                $indicatorDomain->parent_vertical_id = $indicatorOld->parent_vertical_id;
                $indicatorDomain->parent_horizontal_id = $indicatorOld->parent_horizontal_id;

                if (count($indicatorOld->validity) > 0) { //masa berlaku lama tidak nol
                    $monthsOld = array_keys($indicatorOld->validity);
                    $monthsNew = array_keys($indicatorNew->validity);

                    //new VS old months
                    $new = [];
                    $i = 0;
                    foreach ($monthsNew as $monthNew) {
                        if (in_array($monthNew, $monthsOld) === false) {
                            $new[$i] = $monthNew;
                            $i++;
                        }
                    }

                    if (count($new) > 0) { //terdapat selisih antara masa berlaku baru dengan lama
                        foreach ($new as $v) {
                            $targetDomain->id = (string) Str::orderedUuid();
                            $targetDomain->indicator_id = $id;
                            $targetDomain->month = $v;
                            $targetDomain->value = 0;
                            $targetDomain->locked = true;
                            $targetDomain->default = true;

                            $this->targetRepository->save($targetDomain); //save target

                            $realizationDomain->id = (string) Str::orderedUuid();
                            $realizationDomain->indicator_id = $id;
                            $realizationDomain->month = $v;
                            $realizationDomain->value = 0;
                            $realizationDomain->locked = true;
                            $realizationDomain->default = true;

                            $this->realizationRepository->save($realizationDomain); //save realisasi
                        }
                    }

                    //old VS new months
                    $old = [];
                    $i = 0;
                    foreach ($monthsOld as $monthOld) {
                        if (in_array($monthOld, $monthsNew) === false) {
                            $old[$i] = $monthOld;
                            $i++;
                        }
                    }

                    if (count($old) > 0) { //terdapat selisih antara masa berlaku lama dengan baru
                        foreach ($old as $v) {
                            $this->targetRepository->delete__by__month_indicatorId($v, $id); //delete target
                            $this->realizationRepository->delete__by__month_indicatorId($v, $id); //delete realisasi
                        }
                    }
                } else { //masa berlaku lama nol
                    if (count($indicatorNew->validity) > 0) {
                        foreach ($indicatorNew->validity as $key => $value) {
                            $targetDomain->id = (string) Str::orderedUuid();
                            $targetDomain->indicator_id = $id;
                            $targetDomain->month = $key;
                            $targetDomain->value = 0;
                            $targetDomain->locked = true;
                            $targetDomain->default = true;

                            $this->targetRepository->save($targetDomain); //save target

                            $realizationDomain->id = (string) Str::orderedUuid();
                            $realizationDomain->indicator_id = $id;
                            $realizationDomain->month = $key;
                            $realizationDomain->value = 0;
                            $realizationDomain->locked = true;
                            $realizationDomain->default = true;

                            $this->realizationRepository->save($realizationDomain); //save realisasi
                        }
                    }
                }

                $this->indicatorRepository->update__by__id($indicatorDomain, $id); //update KPI

                /**
                 * section: childs
                 */

                //semua turunan KPI yang dipilih
                $familiesIndicatorOld = $this->indicatorRepository->findAllByParentVerticalId($id);

                if (count($familiesIndicatorOld) > 0) {
                    foreach ($familiesIndicatorOld as $familyIndicatorOld) {
                        //convert (validity & weight) from array to JSON string
                        $toJson = $this->validity_and_weight__to__JSON($indicatorNew->validity, $indicatorNew->weight);

                        if ($indicatorNew->dummy === '1') {
                            $indicatorDomain->dummy = true;
                            $indicatorDomain->reducing_factor = null;
                            $indicatorDomain->polarity = null;
                            $indicatorDomain->validity = null;
                            $indicatorDomain->weight = null;
                        } else {
                            if ($indicatorNew->reducing_factor === '1') {
                                $indicatorDomain->reducing_factor = true;
                                $indicatorDomain->polarity = null;
                            } else {
                                $indicatorDomain->reducing_factor = false;
                                $indicatorDomain->polarity = $indicatorNew->polarity;
                            }

                            $indicatorDomain->dummy = false;
                            $indicatorDomain->validity = $toJson['validity'];
                            $indicatorDomain->weight =  $toJson['weight'];
                        }

                        $indicatorDomain->indicator = $indicatorNew->indicator;
                        $indicatorDomain->formula = $indicatorNew->formula;
                        $indicatorDomain->measure = $indicatorNew->measure;
                        $indicatorDomain->year = $familyIndicatorOld->year;
                        $indicatorDomain->reviewed = $familyIndicatorOld->reviewed;
                        $indicatorDomain->referenced = $familyIndicatorOld->referenced;
                        $indicatorDomain->label = $familyIndicatorOld->label;
                        $indicatorDomain->unit_id = $familyIndicatorOld->unit_id;
                        $indicatorDomain->level_id = $familyIndicatorOld->level_id;
                        $indicatorDomain->order = $familyIndicatorOld->order;
                        $indicatorDomain->parent_vertical_id = $familyIndicatorOld->parent_vertical_id;
                        $indicatorDomain->parent_horizontal_id = $familyIndicatorOld->parent_horizontal_id;

                        if (count($familyIndicatorOld->validity) > 0) { //masa berlaku lama tidak nol
                            $monthsOld = array_keys($familyIndicatorOld->validity);
                            $monthsNew = array_keys($indicatorNew->validity);

                            //new VS old months
                            $new = [];
                            $i = 0;
                            foreach ($monthsNew as $monthNew) {
                                if (in_array($monthNew, $monthsOld) === false) {
                                    $new[$i] = $monthNew;
                                    $i++;
                                }
                            }

                            if (count($new) > 0) { //terdapat selisih antara masa berlaku baru dengan lama
                                foreach ($new as $v) {
                                    $targetDomain->id = (string) Str::orderedUuid();
                                    $targetDomain->indicator_id = $familyIndicatorOld->id;
                                    $targetDomain->month = $v;
                                    $targetDomain->value = 0;
                                    $targetDomain->locked = true;
                                    $targetDomain->default = true;

                                    $this->targetRepository->save($targetDomain); //save target

                                    $realizationDomain->id = (string) Str::orderedUuid();
                                    $realizationDomain->indicator_id = $familyIndicatorOld->id;
                                    $realizationDomain->month = $v;
                                    $realizationDomain->value = 0;
                                    $realizationDomain->locked = true;
                                    $realizationDomain->default = true;

                                    $this->realizationRepository->save($realizationDomain); //save realisasi
                                }
                            }

                            //old VS new months
                            $old = [];
                            $i = 0;
                            foreach ($monthsOld as $monthOld) {
                                if (in_array($monthOld, $monthsNew) === false) {
                                    $old[$i] = $monthOld;
                                    $i++;
                                }
                            }

                            if (count($old) > 0) { //terdapat selisih antara masa berlaku lama dengan baru
                                foreach ($old as $v) {
                                    $this->targetRepository->delete__by__month_indicatorId($v, $familyIndicatorOld->id); //delete target
                                    $this->realizationRepository->delete__by__month_indicatorId($v, $familyIndicatorOld->id); //delete realisasi
                                }
                            }
                        } else { //masa berlaku lama nol
                            if (count($indicatorNew->validity) > 0) {
                                foreach ($indicatorNew->validity as $key => $value) {
                                    $targetDomain->id = (string) Str::orderedUuid();
                                    $targetDomain->indicator_id = $familyIndicatorOld->id;
                                    $targetDomain->month = $key;
                                    $targetDomain->value = 0;
                                    $targetDomain->locked = true;
                                    $targetDomain->default = true;

                                    $this->targetRepository->save($targetDomain); //save target

                                    $realizationDomain->id = (string) Str::orderedUuid();
                                    $realizationDomain->indicator_id = $familyIndicatorOld->id;
                                    $realizationDomain->month = $key;
                                    $realizationDomain->value = 0;
                                    $realizationDomain->locked = true;
                                    $realizationDomain->default = true;

                                    $this->realizationRepository->save($realizationDomain); //save realisasi
                                }
                            }
                        }

                        $this->indicatorRepository->update__by__id($indicatorDomain, $familyIndicatorOld->id); //update KPI
                    }
                }
            } else if ($indicatorOld->label === 'child') {
                //convert (validity & weight) from array to JSON string
                $toJson = $this->validity_and_weight__to__JSON($indicatorNew->validity, $indicatorNew->weight);

                if ($indicatorNew->dummy === '1') {
                    $indicatorDomain->dummy = true;
                    $indicatorDomain->reducing_factor = null;
                    $indicatorDomain->polarity = null;
                    $indicatorDomain->validity = null;
                    $indicatorDomain->weight = null;
                } else {
                    if ($indicatorNew->reducing_factor === '1') {
                        $indicatorDomain->reducing_factor = true;
                        $indicatorDomain->polarity = null;
                    } else {
                        $indicatorDomain->reducing_factor = false;
                        $indicatorDomain->polarity = $indicatorNew->polarity;
                    }

                    $indicatorDomain->dummy = false;
                    $indicatorDomain->validity = $toJson['validity'];
                    $indicatorDomain->weight =  $toJson['weight'];
                }

                $indicatorDomain->indicator = $indicatorNew->indicator;
                $indicatorDomain->formula = $indicatorNew->formula;
                $indicatorDomain->measure = $indicatorNew->measure;
                $indicatorDomain->year = $indicatorOld->year;
                $indicatorDomain->reviewed = $indicatorOld->reviewed;
                $indicatorDomain->referenced = $indicatorOld->referenced;
                $indicatorDomain->label = $indicatorOld->label;
                $indicatorDomain->unit_id = $indicatorOld->unit_id;
                $indicatorDomain->level_id = $indicatorOld->level_id;
                $indicatorDomain->order = $indicatorOld->order;
                $indicatorDomain->parent_vertical_id = $indicatorOld->parent_vertical_id;
                $indicatorDomain->parent_horizontal_id = $indicatorOld->parent_horizontal_id;

                if (count($indicatorOld->validity) > 0) { //masa berlaku lama tidak nol
                    $monthsOld = array_keys($indicatorOld->validity);
                    $monthsNew = array_keys($indicatorNew->validity);

                    //new VS old months
                    $new = [];
                    $i = 0;
                    foreach ($monthsNew as $monthNew) {
                        if (in_array($monthNew, $monthsOld) === false) {
                            $new[$i] = $monthNew;
                            $i++;
                        }
                    }

                    if (count($new) > 0) { //terdapat selisih antara masa berlaku baru dengan lama
                        foreach ($new as $v) {
                            $targetDomain->id = (string) Str::orderedUuid();
                            $targetDomain->indicator_id = $id;
                            $targetDomain->month = $v;
                            $targetDomain->value = 0;
                            $targetDomain->locked = true;
                            $targetDomain->default = true;

                            $this->targetRepository->save($targetDomain); //save target

                            $realizationDomain->id = (string) Str::orderedUuid();
                            $realizationDomain->indicator_id = $id;
                            $realizationDomain->month = $v;
                            $realizationDomain->value = 0;
                            $realizationDomain->locked = true;
                            $realizationDomain->default = true;

                            $this->realizationRepository->save($realizationDomain); //save realisasi
                        }
                    }

                    //old VS new months
                    $old = [];
                    $i = 0;
                    foreach ($monthsOld as $monthOld) {
                        if (in_array($monthOld, $monthsNew) === false) {
                            $old[$i] = $monthOld;
                            $i++;
                        }
                    }

                    if (count($old) > 0) { //terdapat selisih antara masa berlaku lama dengan baru
                        foreach ($old as $v) {
                            $this->targetRepository->delete__by__month_indicatorId($v, $id); //delete target
                            $this->realizationRepository->delete__by__month_indicatorId($v, $id); //delete realisasi
                        }
                    }
                } else { //masa berlaku lama nol
                    if (count($indicatorNew->validity) > 0) {
                        foreach ($indicatorNew->validity as $key => $value) {
                            $targetDomain->id = (string) Str::orderedUuid();
                            $targetDomain->indicator_id = $id;
                            $targetDomain->month = $key;
                            $targetDomain->value = 0;
                            $targetDomain->locked = true;
                            $targetDomain->default = true;

                            $this->targetRepository->save($targetDomain); //save target

                            $realizationDomain->id = (string) Str::orderedUuid();
                            $realizationDomain->indicator_id = $id;
                            $realizationDomain->month = $key;
                            $realizationDomain->value = 0;
                            $realizationDomain->locked = true;
                            $realizationDomain->default = true;

                            $this->realizationRepository->save($realizationDomain); //save realisasi
                        }
                    }
                }

                $this->indicatorRepository->update__by__id($indicatorDomain, $id); //update KPI
            }
        });
    }

    //use repo IndicatorRepository
    public function destroy(string|int $id) : void
    {
        DB::transaction(function () use ($id) {
            $this->indicatorRepository->delete__by__id($id);
        });
    }

    private function validity_and_weight__to__JSON(?array $validity, ?array $weight) : array
    {
        $jsonString = [];
        if (is_null($validity)) {
            $jsonString['validity'] = null;
            $jsonString['weight'] = null;
        } else {
            $WEIGHT = [];
            $VALIDITY = [];
            foreach ($validity as $key => $value) {
                $WEIGHT[$key] = is_null($weight) || !array_key_exists($key, $weight) ? (float) 0 : (float) $weight[$key];

                $VALIDITY[$key] = true;
            }
            $jsonString['validity'] = collect($VALIDITY)->toJson();
            $jsonString['weight'] = collect($WEIGHT)->toJson();
        }

        return ['validity' => $jsonString['validity'], 'weight' => $jsonString['weight']];
    }
}
