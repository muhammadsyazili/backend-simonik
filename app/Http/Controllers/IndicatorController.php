<?php

namespace App\Http\Controllers;

use App\DTO\IndicatorConstructRequest;
use App\DTO\IndicatorInsertRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Indicator;
use App\Models\Level;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Services\IndicatorService;
use App\Services\IndicatorValidationService;

class IndicatorController extends ApiController
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $indicatorValidationService = new IndicatorValidationService();

        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $indicatorConstructRequenst = new IndicatorConstructRequest();

        $indicatorConstructRequenst->indicatorRepository = $indicatorRepository;
        $indicatorConstructRequenst->levelRepository = $levelRepository;

        $indicatorService = new IndicatorService($indicatorConstructRequenst);

        $validation = $indicatorValidationService->insertValidation($request);

        if($validation->fails()){
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $indicatorInsertRequenst = new IndicatorInsertRequest();

        $indicatorInsertRequenst->validity = $request->post('validity');
        $indicatorInsertRequenst->weight = $request->post('weight');
        $indicatorInsertRequenst->dummy = $request->post('dummy');
        $indicatorInsertRequenst->reducing_factor = $request->post('reducing_factor');
        $indicatorInsertRequenst->polarity = $request->post('polarity');
        $indicatorInsertRequenst->indicator = $request->post('indicator');
        $indicatorInsertRequenst->formula = $request->post('formula');
        $indicatorInsertRequenst->measure = $request->post('measure');
        $indicatorInsertRequenst->user_id = $request->header('X-User-Id');

        $insert = $indicatorService->insert($indicatorInsertRequenst);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indicator creating successfully",
            $insert,
            null,
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $indicatorRepository = new IndicatorRepository();
        $indicatorConstructRequenst = new IndicatorConstructRequest();

        $indicatorConstructRequenst->indicatorRepository = $indicatorRepository;

        $indicatorService = new IndicatorService($indicatorConstructRequenst);

        $indicator = $indicatorService->show($id);
        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indicator creating successfully",
            $indicator,
            null,
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $attributes = [
            'indicator' => ['required', 'string', 'max:100'],
            'dummy' => ['required', 'boolean'],
            'reducing_factor' => ['nullable', 'required_if:dummy,0', 'boolean'],
            'polarity' => ['nullable', 'required_if:dummy,0', 'in:aman,1,-1'],
            'formula' => ['nullable', 'string'],
            'measure' => ['nullable', 'string'],
            'validity' => ['nullable'],
            'weight' => ['nullable'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'required_if' => ':attribute tidak boleh kosong.',
            'max' => [
                'numeric' => ':attribute tidak boleh lebih besar dari :max.',
                'file'    => ':attribute tidak boleh lebih besar dari :max kilobytes.',
                'string'  => ':attribute tidak boleh lebih besar dari :max characters.',
                'array'   => ':attribute tidak boleh lebih dari :max items.',
            ],
            'boolean' => ':attribute harus true atau false.',
            'in' => ':attribute yang dipilih tidak sah.',
            'numeric' => ':attribute harus numerik.',
        ];

        if (!is_null($request->post('validity'))) {
            foreach ($request->post('validity') as $key => $value) {
                $attributes["validity.$key"] = ['in:aman,1'];
            }
        }

        if (!is_null($request->post('weight'))) {
            foreach ($request->post('weight') as $key => $value) {
                $attributes["weight.$key"] = ['numeric'];
            }
        }

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        if($validator->fails()){
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validator->errors(),
            );
        }

        // validity & weight to JSON
        $validity_JsonString = null;
        $weight_JsonString = null;
        if (is_null($request->post('validity'))) {
            $validity_JsonString = null;
            $weight_JsonString = null;
        } else {
            $weight_Array = [];
            $validity_Array = [];
            foreach ($request->post('validity') as $key => $value) {
                $weight_Array[Str::replace("'", null, $key)] = is_null($request->post('weight') || !array_key_exists($key, $request->post('weight'))) ?
                    (float) 0 :
                    (float) $request->post('weight')[$key];

                $validity_Array[Str::replace("'", null, $key)] = (int) $value;
            }
            $validity_JsonString = collect($validity_Array)->toJson();
            $weight_JsonString = collect($weight_Array)->toJson();
        }

        $data = [];
        $id = (string) Str::orderedUuid();
        if ($request->post('dummy') == 1) {
            $data['id'] = $id;
            $data['indicator'] = $request->post('indicator');
            $data['formula'] = $request->post('formula');
            $data['measure'] = $request->post('measure');
            $data['weight'] = null;
            $data['polarity'] = null;
            $data['year'] = null;
            $data['reducing_factor'] = null;
            $data['validity'] = null;
            $data['reviewed'] = 1;
            $data['referenced'] = 0;
            $data['dummy'] = 1;
            $data['label'] = 'super-master';
            $data['unit_id'] = null;
            $data['level_id'] = Level::firstWhere(['slug' => 'super-master'])->id;
            $data['order'] = Indicator::withTrashed()->count()+1;
            $data['parent_vertical_id'] = null;
            $data['parent_horizontal_id'] = null;
            $data['created_by'] = $request->header('X-User-Id');

            $data['created_at'] = \Carbon\Carbon::now();
            $data['updated_at'] = \Carbon\Carbon::now();
        } else {
            if ($request->post('reducing_factor') == 1) {
                $data['id'] = $id;
                $data['indicator'] = $request->post('indicator');
                $data['formula'] = $request->post('formula');
                $data['measure'] = $request->post('measure');
                $data['weight'] =  $weight_JsonString;
                $data['polarity'] = null;
                $data['year'] = null;
                $data['reducing_factor'] = 1;
                $data['validity'] = $validity_JsonString;
                $data['reviewed'] = 1;
                $data['referenced'] = 0;
                $data['dummy'] = 0;
                $data['label'] = 'super-master';
                $data['unit_id'] = null;
                $data['level_id'] = Level::firstWhere(['slug' => 'super-master'])->id;
                $data['order'] = Indicator::withTrashed()->count()+1;
                $data['parent_vertical_id'] = null;
                $data['parent_horizontal_id'] = null;
                $data['created_by'] = $request->header('X-User-Id');

                $data['created_at'] = \Carbon\Carbon::now();
                $data['updated_at'] = \Carbon\Carbon::now();
            } else {
                $data['id'] = $id;
                $data['indicator'] = $request->post('indicator');
                $data['formula'] = $request->post('formula');
                $data['measure'] = $request->post('measure');
                $data['weight'] =  $weight_JsonString;
                $data['polarity'] = $request->post('polarity');
                $data['year'] = null;
                $data['reducing_factor'] = 0;
                $data['validity'] = $validity_JsonString;
                $data['reviewed'] = 1;
                $data['referenced'] = 0;
                $data['dummy'] = 0;
                $data['label'] = 'super-master';
                $data['unit_id'] = null;
                $data['level_id'] = Level::firstWhere(['slug' => 'super-master'])->id;
                $data['order'] = Indicator::withTrashed()->count()+1;
                $data['parent_vertical_id'] = null;
                $data['parent_horizontal_id'] = null;
                $data['created_by'] = $request->header('X-User-Id');

                $data['created_at'] = \Carbon\Carbon::now();
                $data['updated_at'] = \Carbon\Carbon::now();
            }
        }

        $insert = DB::table('indicators')->insert($data);

        if ($insert) {
            $affected = DB::table('indicators')->where(['id' => $id])->update(['code' => $id]);
        }

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indicator creating successfully",
            $affected,
            null,
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $indicator = Indicator::with(['childsVertical'])->findOrFail($id);

        dd($indicator->toArray());
        if ($indicator->label === 'master') {
            foreach ($indicator->childs_vertical as $childs_vertical) {
                Indicator::where(['id' => $childs_vertical->id])->forceDelete();
            }
        }
    }
}
