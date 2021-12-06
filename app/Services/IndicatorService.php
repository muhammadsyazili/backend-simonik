<?php

namespace App\Services;

use App\Domains\Indicator;
use Illuminate\Http\Request;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use Illuminate\Support\Str;

class IndicatorService {
    private IndicatorRepository $indicatorRepository;
    private LevelRepository $levelRepository;

    public function __construct(IndicatorRepository $indicatorRepository, LevelRepository $levelRepository)
    {
        $this->indicatorRepository = $indicatorRepository;
        $this->levelRepository = $levelRepository;
    }

    public function insert(Request $request) : mixed
    {
        $indicator = new Indicator();

        $toJson = $this->validity_and_weight_ToJson($request->post('validity'), $request->post('weight'));

        if ($request->post('dummy') == 1) { //indikator merupakan dummy
            $indicator->weight = null;
            $indicator->polarity = null;
            $indicator->reducing_factor = null;
            $indicator->validity = null;
            $indicator->dummy = true;
        } else {
            if ($request->post('reducing_factor') == 1) { //indikator merupakan faktor pengurang
                $indicator->polarity = null;
                $indicator->reducing_factor = true;
            } else { //indikator bukan merupakan faktor pengurang
                $indicator->polarity = $request->post('polarity');
                $indicator->reducing_factor = false;
            }

            $indicator->weight =  $toJson['weight'];
            $indicator->validity = $toJson['validity'];
            $indicator->dummy = false;
        }

        $id = (string) Str::orderedUuid();

        $indicator->id = $id;
        $indicator->indicator = $request->post('indicator');
        $indicator->formula = $request->post('formula');
        $indicator->measure = $request->post('measure');
        $indicator->year = null;
        $indicator->reviewed = true;
        $indicator->referenced = false;
        $indicator->label = 'super-master';
        $indicator->unit_id = null;
        $indicator->level_id = $this->levelRepository->findIdBySlug('super-master');
        $indicator->order = $this->indicatorRepository->countOrderColumn();
        $indicator->parent_vertical_id = null;
        $indicator->parent_horizontal_id = null;
        $indicator->created_by = $request->header('X-User-Id');

        $insert = $this->indicatorRepository->save($indicator);

        if ($insert) {
            return $this->indicatorRepository->updateCodeColumn($id);
        }
    }

    private function validity_and_weight_ToJson($validity, $weight)
    {
        $jsonString = [];
        if (is_null($validity)) {
            $jsonString['validity'] = null;
            $jsonString['weight'] = null;
        } else {
            $WEIGHT = [];
            $VALIDITY = [];
            foreach ($validity as $key => $value) {
                $WEIGHT[Str::replace("'", null, $key)] = is_null($weight || !array_key_exists($key, $weight)) ?
                    (float) 0 :
                    (float) $weight[$key];

                $VALIDITY[Str::replace("'", null, $key)] = (int) $value;
            }
            $jsonString['validity'] = collect($VALIDITY)->toJson();
            $jsonString['weight'] = collect($WEIGHT)->toJson();
        }

        return ['validity' => $jsonString['validity'], 'weight' => $jsonString['weight']];
    }
}
