<?php

namespace App\Repositories;

use App\Domains\Indicator;
use Illuminate\Support\Facades\DB;
use App\Models\Indicator as IndicatorModel;

class IndicatorRepository {
    public function save(Indicator $indicator) : bool
    {
        $data['id'] = $indicator->id;
        $data['indicator'] = $indicator->indicator;
        $data['formula'] = $indicator->formula;
        $data['measure'] = $indicator->measure;
        $data['weight'] =  $indicator->weight;
        $data['polarity'] = $indicator->polarity;
        $data['year'] = $indicator->year;
        $data['reducing_factor'] = $indicator->reducing_factor;
        $data['validity'] = $indicator->validity;
        $data['reviewed'] = $indicator->reviewed;
        $data['referenced'] = $indicator->referenced;
        $data['dummy'] = $indicator->dummy;
        $data['label'] = $indicator->label;
        $data['unit_id'] = $indicator->unit_id;
        $data['level_id'] = $indicator->level_id;
        $data['order'] = $indicator->order;
        $data['parent_vertical_id'] = $indicator->parent_vertical_id;
        $data['parent_horizontal_id'] = $indicator->parent_horizontal_id;
        $data['created_by'] = $indicator->created_by;

        $data['created_at'] = \Carbon\Carbon::now();
        $data['updated_at'] = \Carbon\Carbon::now();

        return DB::table('indicators')->insert($data);
    }

    public function countOrderColumn()
    {
        return IndicatorModel::withTrashed()->count()+1;
    }

    public function updateCodeColumn($id)
    {
        return DB::table('indicators')->where(['id' => $id])->update(['code' => $id]);
    }

    public function findById($id)
    {
        return IndicatorModel::findOrFail($id);
    }
}
