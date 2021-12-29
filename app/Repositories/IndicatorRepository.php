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
        $data['code'] = is_null($indicator->code) ? null : $indicator->code;
        $data['parent_vertical_id'] = $indicator->parent_vertical_id;
        $data['parent_horizontal_id'] = $indicator->parent_horizontal_id;
        $data['created_by'] = $indicator->created_by;

        $data['created_at'] = \Carbon\Carbon::now();
        $data['updated_at'] = \Carbon\Carbon::now();

        DB::table('indicators')->insert($data);
    }

    public function updateById(Indicator $indicator, string|int $id) : void
    {
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

        $data['updated_at'] = \Carbon\Carbon::now();

        DB::table('indicators')->where(['id' => $id])->update($data);
    }

    public function countOrderColumn() : int
    {
        return ModelsIndicator::withTrashed()->count()+1;
    }

    public function countCodeColumnById(string|int $id) : int
    {
        return ModelsIndicator::where(['code' => $id])->count();
    }

    public function countByWhere(array $where) : int
    {
        return ModelsIndicator::where($where)->count();
    }

    public function updateCodeColumnById(string|int $id) : void
    {
        DB::table('indicators')->where(['id' => $id])->update(['code' => $id]);
    }

    public function updateReferenceById(string|int $id, string|int|null $parentHorizontalId) : void
    {
        ModelsIndicator::where(['id' => $id])->update(['parent_horizontal_id' => $parentHorizontalId, 'referenced' => 1]);
    }

    public function deleteByWhere(array $where) : void
    {
        ModelsIndicator::where($where)->forceDelete();
    }

    public function findById(string|int $id)
    {
        return ModelsIndicator::findOrFail($id);
    }

    public function findLabelColumnById(string|int $id) : string
    {
        return ModelsIndicator::firstWhere(['id' => $id])->label;
    }

    public function findWithLevelById(string|int $id)
    {
        return ModelsIndicator::with('level')->findOrFail($id);
    }

    public function findIdAndParentHorizontalIdByWhere(array $where) : array
    {
        return ModelsIndicator::where($where)->get(['id', 'parent_horizontal_id'])->toArray();
    }

    public function findIdByWhere(array $where) : string|int
    {
        return ModelsIndicator::firstWhere($where)->id;
    }

    public function findAllNotReferencedBySuperMasterLabel()
    {
        return ModelsIndicator::notReferenced()->where(['label' => 'super-master'])->get();
    }

    public function findAllWithChildsBySuperMasterLabel()
    {
        return ModelsIndicator::with('childsHorizontalRecursive')->rootHorizontal()->where(['label' => 'super-master'])->get();
    }

    public function findAllReferencedWithChildsByWhere(array $where)
    {
        return ModelsIndicator::with('childsHorizontalRecursive')->referenced()->rootHorizontal()->where($where)->get();
    }

    public function findAllReferencedBySuperMasterLabel()
    {
        return ModelsIndicator::with('childsHorizontalRecursive')->referenced()->rootHorizontal()->where(['label' => 'super-master'])->get();
    }

    public function findAllWithTargetsAndRealizationsByWhere(array $where)
    {
        return ModelsIndicator::with(['targets', 'realizations'])->where($where)->get();
    }

    public function findAllWithChildsTargetsRealizationsByWhere(array $where)
    {
        return ModelsIndicator::with(['targets', 'realizations', 'childsHorizontalRecursive'])->referenced()->rootHorizontal()->where($where)->get();
    }

    public function findAllIdBySuperMasterLabel() : array
    {
        return ModelsIndicator::where(['label' => 'super-master'])->get(['id'])->toArray();
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

    public function findAllByParentVerticalId(string|int $parentVerticalId)
    {
        return ModelsIndicator::where(['parent_vertical_id' => $parentVerticalId])->get();
    }

    public function findAllIsChildByLevelIdAndUnitIdAndYear(string|int $levelId, string|int|null $unitId, string $year)
    {
        return is_null($unitId) ? ModelsIndicator::referenced()->where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->get() : ModelsIndicator::referenced()->where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->get();
    }
}
