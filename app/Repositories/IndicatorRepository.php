<?php

namespace App\Repositories;

use App\Domains\Indicator;
use Illuminate\Support\Facades\DB;
use App\Models\Indicator as IndicatorModel;
use App\Models\Level;
use App\Models\Unit;

class IndicatorRepository {
    public function save(Indicator $indicator) : void
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

        DB::table('indicators')->insert($data);
    }

    public function countOrderColumn()
    {
        return IndicatorModel::withTrashed()->count()+1;
    }

    public function updateCodeColumn($id) : void
    {
        DB::table('indicators')->where(['id' => $id])->update(['code' => $id]);
    }

    public function findById($id)
    {
        return IndicatorModel::findOrFail($id);
    }

    public function findAllSuperMasterLevelNotReferenced()
    {
        return IndicatorModel::notReferenced()->where(['label' => 'super-master'])->get();
    }

    public function findAllPreference()
    {
        return IndicatorModel::with('childsHorizontalRecursive')->rootHorizontal()->where(['label' => 'super-master'])->get();
    }

    public function findAllIdBySuperMasterLevel()
    {
        return IndicatorModel::where(['label' => 'super-master'])->get(['id'])->toArray();
    }

    public function insertReferenceByIndicator($indicator, $preference) : void
    {
        IndicatorModel::where(['id' => $indicator])->update(['parent_horizontal_id' => $preference, 'referenced' => 1]);
    }

    public function findAllWithChildByLevelUnitYear(array $where)
    {
        return IndicatorModel::with('childsHorizontalRecursive')->referenced()->rootHorizontal()->where($where)->get();
    }

    public function findIdParentHorizontalIdByLevelUnitYear(array $where)
    {
        return IndicatorModel::where($where)->get(['id', 'parent_horizontal_id'])->toArray();
    }
}
