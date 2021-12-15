<?php

namespace App\Repositories;

use App\Domains\Indicator;
use Illuminate\Support\Facades\DB;
use App\Models\Indicator as ModelsIndicator;
use App\Models\IndicatorOnlyId as ModelsIndicatorOnlyId;

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

    public function countOrderColumn() : int
    {
        return ModelsIndicator::withTrashed()->count()+1;
    }

    public function updateCodeColumnById(string|int $id) : void
    {
        DB::table('indicators')->where(['id' => $id])->update(['code' => $id]);
    }

    public function findById(string|int $id)
    {
        return ModelsIndicator::findOrFail($id);
    }

    public function findAllNotReferencedBySuperMasterLabel()
    {
        return ModelsIndicator::notReferenced()->where(['label' => 'super-master'])->get();
    }

    public function findAllWithChildsBySuperMasterLabel()
    {
        return ModelsIndicator::with('childsHorizontalRecursive')->rootHorizontal()->where(['label' => 'super-master'])->get();
    }

    public function findAllIdBySuperMasterLabel() : array
    {
        return ModelsIndicator::where(['label' => 'super-master'])->get(['id'])->toArray();
    }

    public function updateReferenceById(string|int $id, string|int|null $parent_horizontal_id) : void
    {
        ModelsIndicator::where(['id' => $id])->update(['parent_horizontal_id' => $parent_horizontal_id, 'referenced' => 1]);
    }

    public function findAllReferencedWithChildsByWhere(array $where)
    {
        return ModelsIndicator::with('childsHorizontalRecursive')->referenced()->rootHorizontal()->where($where)->get();
    }

    public function findIdAndParentHorizontalIdByWhere(array $where) : array
    {
        return ModelsIndicator::where($where)->get(['id', 'parent_horizontal_id'])->toArray();
    }

    public function findIdByWhere(array $where) : string|int
    {
        return ModelsIndicator::firstWhere($where)->id;
    }

    public function countByWhere(array $where) : int
    {
        return ModelsIndicator::where($where)->count();
    }

    public function findAllIdReferencedBySuperMasterLabel() : array
    {
        return ModelsIndicator::referenced()->where(['label' => 'super-master'])->get(['id'])->toArray();
    }

    public function findAllWithParentsById(string|int $id) : array
    {
        return ModelsIndicatorOnlyId::with('parentHorizontalRecursive')->where(['id' => $id])->get()->toArray();
    }

    public function findAllById(array $id)
    {
        return ModelsIndicator::whereIn('id', $id)->get();
    }

    public function findAllByWhere(array $where)
    {
        return ModelsIndicator::where($where)->get();
    }

    public function findAllWithTargetsAndRealizationsByWhere(array $where)
    {
        return ModelsIndicator::with(['targets', 'realizations'])->where($where)->get();
    }

    public function deleteByWhere(array $where) : void
    {
        ModelsIndicator::where($where)->forceDelete();
    }
}
