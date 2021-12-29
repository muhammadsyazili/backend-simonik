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
    public function store(IndicatorInsertOrUpdateRequest $indicatorNew) : void
    {
        $indicator = new Indicator();

        DB::transaction(function () use ($indicatorNew, $indicator) {
            $toJson = $this->validityNweightToJSON($indicatorNew->validity, $indicatorNew->weight);

            if ($indicatorNew->dummy === '1') {
                $indicator->dummy = true;
                $indicator->reducing_factor = null;
                $indicator->polarity = null;
                $indicator->validity = null;
                $indicator->weight = null;
            } else {
                if ($indicatorNew->reducing_factor === '1') {
                    $indicator->reducing_factor = true;
                    $indicator->polarity = null;
                } else {
                    $indicator->reducing_factor = false;
                    $indicator->polarity = $indicatorNew->polarity;
                }

                $indicator->dummy = false;
                $indicator->validity = $toJson['validity'];
                $indicator->weight =  $toJson['weight'];
            }

            $id = (string) Str::orderedUuid();

            $indicator->id = $id;
            $indicator->indicator = $indicatorNew->indicator;
            $indicator->formula = $indicatorNew->formula;
            $indicator->measure = $indicatorNew->measure;
            $indicator->year = null;
            $indicator->reviewed = true;
            $indicator->referenced = false;
            $indicator->label = 'super-master';
            $indicator->unit_id = null;
            $indicator->level_id = $this->levelRepository->findIdBySlug('super-master');
            $indicator->order = $this->indicatorRepository->countOrderColumn();
            $indicator->code = null;
            $indicator->parent_vertical_id = null;
            $indicator->parent_horizontal_id = null;
            $indicator->created_by = $indicatorNew->user_id;

            $this->indicatorRepository->save($indicator);
            $this->indicatorRepository->updateCodeColumnById($id);
        });
    }

    //use repo IndicatorRepository
    public function edit(string|int $id)
    {
        $indicator = $this->indicatorRepository->findWithLevelById($id);
        $indicator->original_polarity = $indicator->getRawOriginal('polarity');
        return $indicator;
    }

    //use repo IndicatorRepository, TargetRepository, RealizationRepository
    public function update(IndicatorInsertOrUpdateRequest $indicatorNew, string|int $id) : void
    {
        //logging
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();

        $indicator = new Indicator();
        $target = new Target();
        $realization = new Realization();

        DB::transaction(function () use ($indicatorNew, $id, $indicator, $target, $realization, $output) {

            $indicatorOld = $this->indicatorRepository->findById($id);

            if ($indicatorOld->label === 'super-master') {
                $output->writeln('--------------------------------');
                $output->writeln('update -> super master');
                $output->writeln('--------------------------------');

                //convert (validity & weight) from array to JSON string
                $toJson = $this->validityNweightToJSON($indicatorNew->validity, $indicatorNew->weight);

                if ($indicatorNew->dummy === '1') {
                    $indicator->dummy = true;
                    $indicator->reducing_factor = null;
                    $indicator->polarity = null;
                    $indicator->validity = null;
                    $indicator->weight = null;
                } else {
                    if ($indicatorNew->reducing_factor === '1') {
                        $indicator->reducing_factor = true;
                        $indicator->polarity = null;
                    } else {
                        $indicator->reducing_factor = false;
                        $indicator->polarity = $indicatorNew->polarity;
                    }

                    $indicator->dummy = false;
                    $indicator->validity = $toJson['validity'];
                    $indicator->weight =  $toJson['weight'];
                }

                $indicator->indicator = $indicatorNew->indicator;
                $indicator->formula = $indicatorNew->formula;
                $indicator->measure = $indicatorNew->measure;
                $indicator->year = $indicatorOld->year;
                $indicator->reviewed = $indicatorOld->reviewed;
                $indicator->referenced = $indicatorOld->referenced;
                $indicator->label = $indicatorOld->label;
                $indicator->unit_id = $indicatorOld->unit_id;
                $indicator->level_id = $indicatorOld->level_id;
                $indicator->order = $indicatorOld->order;
                $indicator->parent_vertical_id = $indicatorOld->parent_vertical_id;
                $indicator->parent_horizontal_id = $indicatorOld->parent_horizontal_id;

                $this->indicatorRepository->updateById($indicator, $id); //update indikator
            } else if ($indicatorOld->label === 'master') {
                $output->writeln('--------------------------------');
                $output->writeln('update -> master');
                $output->writeln('--------------------------------');

                /**
                 * section: master
                 */

                //convert (validity & weight) from array to JSON string
                $toJson = $this->validityNweightToJSON($indicatorNew->validity, $indicatorNew->weight);

                if ($indicatorNew->dummy === '1') {
                    $indicator->dummy = true;
                    $indicator->reducing_factor = null;
                    $indicator->polarity = null;
                    $indicator->validity = null;
                    $indicator->weight = null;
                } else {
                    if ($indicatorNew->reducing_factor === '1') {
                        $indicator->reducing_factor = true;
                        $indicator->polarity = null;
                    } else {
                        $indicator->reducing_factor = false;
                        $indicator->polarity = $indicatorNew->polarity;
                    }

                    $indicator->dummy = false;
                    $indicator->validity = $toJson['validity'];
                    $indicator->weight =  $toJson['weight'];
                }

                $indicator->indicator = $indicatorNew->indicator;
                $indicator->formula = $indicatorNew->formula;
                $indicator->measure = $indicatorNew->measure;
                $indicator->year = $indicatorOld->year;
                $indicator->reviewed = $indicatorOld->reviewed;
                $indicator->referenced = $indicatorOld->referenced;
                $indicator->label = $indicatorOld->label;
                $indicator->unit_id = $indicatorOld->unit_id;
                $indicator->level_id = $indicatorOld->level_id;
                $indicator->order = $indicatorOld->order;
                $indicator->parent_vertical_id = $indicatorOld->parent_vertical_id;
                $indicator->parent_horizontal_id = $indicatorOld->parent_horizontal_id;

                if (count($indicatorOld->validity) > 0) { //masa berlaku lama tidak nol
                    $output->writeln('--------------------------------');
                    $output->writeln('masa berlaku lama tidak nol');
                    $output->writeln(sprintf('masa berlaku lama: %s', json_encode(array_keys($indicatorOld->validity))));
                    $output->writeln(sprintf('masa berlaku baru: %s', json_encode(array_keys($indicatorNew->validity))));
                    $output->writeln('--------------------------------');

                    $monthsOld = array_keys($indicatorOld->validity);
                    $monthsNew = array_keys($indicatorNew->validity);

                    //new VS old months
                    $new = [];
                    $i = 0;
                    foreach ($monthsNew as $monthNew) {
                        if (array_search($monthNew, $monthsOld) === false) {
                            $new[$i] = $monthNew;
                            $i++;
                        }
                    }

                    if (count($new) > 0) { //terdapat selisih antara masa berlaku baru dengan lama
                        $output->writeln('--------------------------------');
                        $output->writeln('terdapat selisih antara masa berlaku baru dengan lama');
                        $output->writeln(sprintf('masa berlaku baru yang selisih: %s', json_encode($new)));
                        $output->writeln('--------------------------------');

                        foreach ($new as $v) {
                            $target->id = (string) Str::orderedUuid();
                            $target->indicator_id = $id;
                            $target->month = $v;
                            $target->value = 0;
                            $target->locked = true;
                            $target->default = true;

                            $this->targetRepository->save($target); //save target

                            $realization->id = (string) Str::orderedUuid();
                            $realization->indicator_id = $id;
                            $realization->month = $v;
                            $realization->value = 0;
                            $realization->locked = true;
                            $realization->default = true;

                            $this->realizationRepository->save($realization); //save realisasi
                        }
                    }

                    //old VS new months
                    $old = [];
                    $i = 0;
                    foreach ($monthsOld as $monthOld) {
                        if (array_search($monthOld, $monthsNew) === false) {
                            $old[$i] = $monthOld;
                            $i++;
                        }
                    }

                    if (count($old) > 0) { //terdapat selisih antara masa berlaku lama dengan baru
                        $output->writeln('--------------------------------');
                        $output->writeln('terdapat selisih antara masa berlaku lama dengan baru');
                        $output->writeln(sprintf('masa berlaku lama yang selisih: %s', json_encode($old)));
                        $output->writeln('--------------------------------');

                        foreach ($old as $v) {
                            $this->targetRepository->deleteByMonthAndIndicatorId($v, $id); //delete target
                            $this->realizationRepository->deleteByMonthAndIndicatorId($v, $id); //delete realisasi
                        }
                    }
                } else { //masa berlaku lama nol
                    $output->writeln('--------------------------------');
                    $output->writeln('masa berlaku lama nol');
                    $output->writeln('--------------------------------');

                    if (count($indicatorNew->validity) > 0) {
                        $output->writeln('--------------------------------');
                        $output->writeln('masa berlaku baru tidak nol');
                        $output->writeln('--------------------------------');

                        foreach ($indicatorNew->validity as $key => $value) {
                            $target->id = (string) Str::orderedUuid();
                            $target->indicator_id = $id;
                            $target->month = $key;
                            $target->value = 0;
                            $target->locked = true;
                            $target->default = true;

                            $this->targetRepository->save($target); //save target

                            $realization->id = (string) Str::orderedUuid();
                            $realization->indicator_id = $id;
                            $realization->month = $key;
                            $realization->value = 0;
                            $realization->locked = true;
                            $realization->default = true;

                            $this->realizationRepository->save($realization); //save realisasi
                        }
                    }
                }

                $this->indicatorRepository->updateById($indicator, $id); //update indikator

                /**
                 * section: childs
                 */

                //semua turunan indikator yang dipilih
                $familiesIndicatorOld = $this->indicatorRepository->findAllByParentVerticalId($id);

                $output->writeln('--------------------------------');
                $output->writeln(sprintf('jumlah keluarga indikator: %d', count($familiesIndicatorOld)));
                $output->writeln('--------------------------------');

                if (count($familiesIndicatorOld) > 0) {
                    foreach ($familiesIndicatorOld as $familyIndicatorOld) {
                        //convert (validity & weight) from array to JSON string
                        $toJson = $this->validityNweightToJSON($indicatorNew->validity, $indicatorNew->weight);

                        if ($indicatorNew->dummy === '1') {
                            $indicator->dummy = true;
                            $indicator->reducing_factor = null;
                            $indicator->polarity = null;
                            $indicator->validity = null;
                            $indicator->weight = null;
                        } else {
                            if ($indicatorNew->reducing_factor === '1') {
                                $indicator->reducing_factor = true;
                                $indicator->polarity = null;
                            } else {
                                $indicator->reducing_factor = false;
                                $indicator->polarity = $indicatorNew->polarity;
                            }

                            $indicator->dummy = false;
                            $indicator->validity = $toJson['validity'];
                            $indicator->weight =  $toJson['weight'];
                        }

                        $indicator->indicator = $indicatorNew->indicator;
                        $indicator->formula = $indicatorNew->formula;
                        $indicator->measure = $indicatorNew->measure;
                        $indicator->year = $familyIndicatorOld->year;
                        $indicator->reviewed = $familyIndicatorOld->reviewed;
                        $indicator->referenced = $familyIndicatorOld->referenced;
                        $indicator->label = $familyIndicatorOld->label;
                        $indicator->unit_id = $familyIndicatorOld->unit_id;
                        $indicator->level_id = $familyIndicatorOld->level_id;
                        $indicator->order = $familyIndicatorOld->order;
                        $indicator->parent_vertical_id = $familyIndicatorOld->parent_vertical_id;
                        $indicator->parent_horizontal_id = $familyIndicatorOld->parent_horizontal_id;

                        if (count($familyIndicatorOld->validity) > 0) { //masa berlaku lama tidak nol
                            $output->writeln('--------------------------------');
                            $output->writeln('masa berlaku lama tidak nol');
                            $output->writeln(sprintf('masa berlaku lama: %s', json_encode(array_keys($familyIndicatorOld->validity))));
                            $output->writeln(sprintf('masa berlaku baru: %s', json_encode(array_keys($indicatorNew->validity))));
                            $output->writeln('--------------------------------');

                            $monthsOld = array_keys($familyIndicatorOld->validity);
                            $monthsNew = array_keys($indicatorNew->validity);

                            //new VS old months
                            $new = [];
                            $i = 0;
                            foreach ($monthsNew as $monthNew) {
                                if (array_search($monthNew, $monthsOld) === false) {
                                    $new[$i] = $monthNew;
                                    $i++;
                                }
                            }

                            if (count($new) > 0) { //terdapat selisih antara masa berlaku baru dengan lama
                                $output->writeln('--------------------------------');
                                $output->writeln('terdapat selisih antara masa berlaku baru dengan lama');
                                $output->writeln(sprintf('masa berlaku baru yang selisih: %s', json_encode($new)));
                                $output->writeln('--------------------------------');

                                foreach ($new as $v) {
                                    $target->id = (string) Str::orderedUuid();
                                    $target->indicator_id = $familyIndicatorOld->id;
                                    $target->month = $v;
                                    $target->value = 0;
                                    $target->locked = true;
                                    $target->default = true;

                                    $this->targetRepository->save($target); //save target

                                    $realization->id = (string) Str::orderedUuid();
                                    $realization->indicator_id = $familyIndicatorOld->id;
                                    $realization->month = $v;
                                    $realization->value = 0;
                                    $realization->locked = true;
                                    $realization->default = true;

                                    $this->realizationRepository->save($realization); //save realisasi
                                }
                            }

                            //old VS new months
                            $old = [];
                            $i = 0;
                            foreach ($monthsOld as $monthOld) {
                                if (array_search($monthOld, $monthsNew) === false) {
                                    $old[$i] = $monthOld;
                                    $i++;
                                }
                            }

                            if (count($old) > 0) { //terdapat selisih antara masa berlaku lama dengan baru
                                $output->writeln('--------------------------------');
                                $output->writeln('terdapat selisih antara masa berlaku lama dengan baru');
                                $output->writeln(sprintf('masa berlaku lama yang selisih: %s', json_encode($old)));
                                $output->writeln('--------------------------------');

                                foreach ($old as $v) {
                                    $this->targetRepository->deleteByMonthAndIndicatorId($v, $familyIndicatorOld->id); //delete target
                                    $this->realizationRepository->deleteByMonthAndIndicatorId($v, $familyIndicatorOld->id); //delete realisasi
                                }
                            }
                        } else { //masa berlaku lama nol
                            $output->writeln('--------------------------------');
                            $output->writeln('masa berlaku lama nol');
                            $output->writeln('--------------------------------');

                            if (count($indicatorNew->validity) > 0) {
                                $output->writeln('--------------------------------');
                                $output->writeln('masa berlaku baru tidak nol');
                                $output->writeln('--------------------------------');

                                foreach ($indicatorNew->validity as $key => $value) {
                                    $target->id = (string) Str::orderedUuid();
                                    $target->indicator_id = $familyIndicatorOld->id;
                                    $target->month = $key;
                                    $target->value = 0;
                                    $target->locked = true;
                                    $target->default = true;

                                    $this->targetRepository->save($target); //save target

                                    $realization->id = (string) Str::orderedUuid();
                                    $realization->indicator_id = $familyIndicatorOld->id;
                                    $realization->month = $key;
                                    $realization->value = 0;
                                    $realization->locked = true;
                                    $realization->default = true;

                                    $this->realizationRepository->save($realization); //save realisasi
                                }
                            }
                        }

                        $this->indicatorRepository->updateById($indicator, $familyIndicatorOld->id); //update indikator
                    }
                }
            } else if ($indicatorOld->label === 'child') {
                $output->writeln('--------------------------------');
                $output->writeln('update -> child');
                $output->writeln('--------------------------------');

                //convert (validity & weight) from array to JSON string
                $toJson = $this->validityNweightToJSON($indicatorNew->validity, $indicatorNew->weight);

                if ($indicatorNew->dummy === '1') {
                    $indicator->dummy = true;
                    $indicator->reducing_factor = null;
                    $indicator->polarity = null;
                    $indicator->validity = null;
                    $indicator->weight = null;
                } else {
                    if ($indicatorNew->reducing_factor === '1') {
                        $indicator->reducing_factor = true;
                        $indicator->polarity = null;
                    } else {
                        $indicator->reducing_factor = false;
                        $indicator->polarity = $indicatorNew->polarity;
                    }

                    $indicator->dummy = false;
                    $indicator->validity = $toJson['validity'];
                    $indicator->weight =  $toJson['weight'];
                }

                $indicator->indicator = $indicatorNew->indicator;
                $indicator->formula = $indicatorNew->formula;
                $indicator->measure = $indicatorNew->measure;
                $indicator->year = $indicatorOld->year;
                $indicator->reviewed = $indicatorOld->reviewed;
                $indicator->referenced = $indicatorOld->referenced;
                $indicator->label = $indicatorOld->label;
                $indicator->unit_id = $indicatorOld->unit_id;
                $indicator->level_id = $indicatorOld->level_id;
                $indicator->order = $indicatorOld->order;
                $indicator->parent_vertical_id = $indicatorOld->parent_vertical_id;
                $indicator->parent_horizontal_id = $indicatorOld->parent_horizontal_id;

                if (count($indicatorOld->validity) > 0) { //masa berlaku lama tidak nol
                    $output->writeln('--------------------------------');
                    $output->writeln('masa berlaku lama tidak nol');
                    $output->writeln(sprintf('masa berlaku lama: %s', json_encode(array_keys($indicatorOld->validity))));
                    $output->writeln(sprintf('masa berlaku baru: %s', json_encode(array_keys($indicatorNew->validity))));
                    $output->writeln('--------------------------------');

                    $monthsOld = array_keys($indicatorOld->validity);
                    $monthsNew = array_keys($indicatorNew->validity);

                    //new VS old months
                    $new = [];
                    $i = 0;
                    foreach ($monthsNew as $monthNew) {
                        if (array_search($monthNew, $monthsOld) === false) {
                            $new[$i] = $monthNew;
                            $i++;
                        }
                    }

                    if (count($new) > 0) { //terdapat selisih antara masa berlaku baru dengan lama
                        $output->writeln('--------------------------------');
                        $output->writeln('terdapat selisih antara masa berlaku baru dengan lama');
                        $output->writeln(sprintf('masa berlaku baru yang selisih: %s', json_encode($new)));
                        $output->writeln('--------------------------------');

                        foreach ($new as $v) {
                            $target->id = (string) Str::orderedUuid();
                            $target->indicator_id = $id;
                            $target->month = $v;
                            $target->value = 0;
                            $target->locked = true;
                            $target->default = true;

                            $this->targetRepository->save($target); //save target

                            $realization->id = (string) Str::orderedUuid();
                            $realization->indicator_id = $id;
                            $realization->month = $v;
                            $realization->value = 0;
                            $realization->locked = true;
                            $realization->default = true;

                            $this->realizationRepository->save($realization); //save realisasi
                        }
                    }

                    //old VS new months
                    $old = [];
                    $i = 0;
                    foreach ($monthsOld as $monthOld) {
                        if (array_search($monthOld, $monthsNew) === false) {
                            $old[$i] = $monthOld;
                            $i++;
                        }
                    }

                    if (count($old) > 0) { //terdapat selisih antara masa berlaku lama dengan baru
                        $output->writeln('--------------------------------');
                        $output->writeln('terdapat selisih antara masa berlaku lama dengan baru');
                        $output->writeln(sprintf('masa berlaku lama yang selisih: %s', json_encode($old)));
                        $output->writeln('--------------------------------');

                        foreach ($old as $v) {
                            $this->targetRepository->deleteByMonthAndIndicatorId($v, $id); //delete target
                            $this->realizationRepository->deleteByMonthAndIndicatorId($v, $id); //delete realisasi
                        }
                    }
                } else { //masa berlaku lama nol
                    $output->writeln('--------------------------------');
                    $output->writeln('masa berlaku lama nol');
                    $output->writeln('--------------------------------');

                    if (count($indicatorNew->validity) > 0) {
                        $output->writeln('--------------------------------');
                        $output->writeln('masa berlaku baru tidak nol');
                        $output->writeln('--------------------------------');

                        foreach ($indicatorNew->validity as $key => $value) {
                            $target->id = (string) Str::orderedUuid();
                            $target->indicator_id = $id;
                            $target->month = $key;
                            $target->value = 0;
                            $target->locked = true;
                            $target->default = true;

                            $this->targetRepository->save($target); //save target

                            $realization->id = (string) Str::orderedUuid();
                            $realization->indicator_id = $id;
                            $realization->month = $key;
                            $realization->value = 0;
                            $realization->locked = true;
                            $realization->default = true;

                            $this->realizationRepository->save($realization); //save realisasi
                        }
                    }
                }

                $this->indicatorRepository->updateById($indicator, $id); //update indikator
            }
        });
    }

    //use repo IndicatorRepository
    public function destroy(string|int $id) : void
    {
        DB::transaction(function () use ($id) {
            $this->indicatorRepository->deleteByWhere(['id' => $id]);
        });
    }

    private function validityNweightToJSON(?array $validity, ?array $weight) : array
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
