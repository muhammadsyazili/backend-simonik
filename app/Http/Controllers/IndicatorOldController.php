<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Indicator;
use App\Models\Level;

class IndicatorOldController extends ApiController
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
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

        $validator = Validator::make($input, $attributes);

        if($validator->fails()){
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validator->errors(),
            );
        }

        // convert 'validity' & 'weight' to JSON
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
        if ($request->post('dummy') == 1) { //indikator merupakan dummy
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
            if ($request->post('reducing_factor') == 1) { //indikator merupakan faktor pengurang
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
            } else { //indikator bukan merupakan faktor pengurang
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $indicator = Indicator::find($id);
        $indicator->original_polarity = $indicator->getRawOriginal('polarity');
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

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

    }
}
