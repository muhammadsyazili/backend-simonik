<?php

namespace App\Repositories;

use App\Domains\Indicator;
use Illuminate\Support\Facades\DB;
use App\Models\Indicator as ModelsIndicator;
use App\Models\IndicatorOnlyId as ModelsIndicatorOnlyId;
use App\Models\IndicatorOnlyCode as ModelsIndicatorOnlyCode;

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

    public function countAllPlusOneByLevelIdAndUnitIdAndYear(string|int $levelId, string|int|null $unitId = null, string|int|null $year = null) : int
    {
        if ($levelId === 'super-master') {
            return ModelsIndicator::where(['label' => 'super-master'])->withTrashed()->count()+1;
        } else {
            return is_null($unitId) ?
            ModelsIndicator::where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->count()+1 :
            ModelsIndicator::where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->count()+1;
        }
    }

    public function countAllByCode(string|int $code) : int
    {
        return ModelsIndicator::where(['code' => $code])->count();
    }

    public function countAllByIdListAndSuperMasterLabel(array $idList) : int
    {
        return ModelsIndicator::where(['label' => 'super-master'])->whereIn('id', $idList)->count();
    }

    public function countAllByLevelIdAndYear(string|int $levelId, string|int $year) : int
    {
        return ModelsIndicator::where(['level_id' => $levelId, 'year' => $year])->count();
    }

    public function countAllByLevelIdAndUnitIdAndYear(string|int $levelId, string|int|null $unitId = null, string|int $year) : int
    {
        return is_null($unitId) ?
        ModelsIndicator::where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->count() :
        ModelsIndicator::where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->count();
    }

    public function countAllByCodeAndLevelIdAndUnitIdAndYear(string|int $code, string|int $levelId, string|int|null $unitId = null, string|int $year) : int
    {
        return is_null($unitId) ?
        ModelsIndicator::where(['label' => 'master', 'code' => $code, 'level_id' => $levelId, 'year' => $year])->count() :
        ModelsIndicator::where(['label' => 'child', 'code' => $code, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->count();
    }

    public function updateCodeById(string|int $id) : void
    {
        DB::table('indicators')->where(['id' => $id])->update(['code' => $id]);
    }

    public function updateOrderById(int $order, string|int $id) : void
    {
        ModelsIndicator::where(['id' => $id])->update(['order' => $order]);
    }

    public function updateOrderByParentVerticalId(int $order, string|int $parentVerticalId) : void
    {
        ModelsIndicator::where(['parent_vertical_id' => $parentVerticalId])->update(['order' => $order]);
    }

    public function updateParentHorizontalIdAndReferencedById(string|int $id, string|int|null $parentHorizontalId = null) : void
    {
        ModelsIndicator::where(['id' => $id])->update(['parent_horizontal_id' => $parentHorizontalId, 'referenced' => 1]);
    }

    public function deleteByLevelIdAndUnitIdAndYear(string|int $levelId, string|int|null $unitId = null, string|int $year) : void
    {
        is_null($unitId) ?
        ModelsIndicator::where(['level_id' => $levelId, 'year' => $year])->forceDelete() :
        ModelsIndicator::where(['level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->forceDelete();
    }

    public function deleteById(string|int $id) : void
    {
        ModelsIndicator::where(['id' => $id])->forceDelete();
    }

    public function findById(string|int $id)
    {
        return ModelsIndicator::findOrFail($id);
    }

    public function findLabelById(string|int $id) : string
    {
        return ModelsIndicator::firstWhere(['id' => $id])->label;
    }

    public function findByCodeAndLevelIdAndUnitIdAndYear(string|int $code, string|int $levelId, string|int|null $unitId = null, string|int $year)
    {
        return is_null($unitId) ?
        ModelsIndicator::firstWhere(['label' => 'master', 'code' => $code, 'level_id' => $levelId, 'year' => $year]) :
        ModelsIndicator::firstWhere(['label' => 'child', 'code' => $code, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year]);
    }

    public function findIdByCodeAndLevelIdAndUnitIdAndYear(string|int $code, string|int $levelId, string|int|null $unitId = null, string|int $year) : string|int
    {
        return is_null($unitId) ?
        ModelsIndicator::firstWhere(['label' => 'master', 'code' => $code, 'level_id' => $levelId, 'year' => $year])->id :
        ModelsIndicator::firstWhere(['label' => 'child', 'code' => $code, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->id;
    }

    public function findWithLevelById(string|int $id)
    {
        return ModelsIndicator::with('level')->findOrFail($id);
    }

    public function findIdAndParentHorizontalIdByWhere(string $label, string|int|null $levelId = null, string|int|null $unitId = null, string|int|null $year = null) : array
    {
        return $label === 'super-master' ?
        ModelsIndicator::where(['label' => 'super-master'])->orderBy('order', 'asc')->get(['id', 'parent_horizontal_id'])->toArray() :
        ModelsIndicator::where(['label' => $label, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->orderBy('order', 'asc')->get(['id', 'parent_horizontal_id'])->toArray();
    }

    public function findIdByParentVerticalIdAndLevelIdAndUnitIdAndYear(string|int $parentVerticalId, string|int $levelId, string|int $unitId, string|int $year) : string|int
    {
        return ModelsIndicator::firstWhere(['parent_vertical_id' => $parentVerticalId, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->id;
    }

    public function findAllNotReferencedBySuperMasterLabel()
    {
        return ModelsIndicator::notReferenced()->where(['label' => 'super-master'])->orderBy('order', 'asc')->get();
    }

    public function findAllWithChildsBySuperMasterLabel()
    {
        return ModelsIndicator::with('childsHorizontalRecursive')->rootHorizontal()->where(['label' => 'super-master'])->orderBy('order', 'asc')->get();
    }

    public function findAllReferencedAndRootHorizontalWithChildsByLabelAndLevelIdAndUnitIdAndYear(string $label, string|int|null $levelId = null, string|int|null $unitId = null, string|int|null $year = null)
    {
        return $label === 'super-master' ?
        ModelsIndicator::with('childsHorizontalRecursive')->referenced()->rootHorizontal()->where(['label' => 'super-master'])->orderBy('order', 'asc')->get() :
        ModelsIndicator::with('childsHorizontalRecursive')->referenced()->rootHorizontal()->where(['label' => $label, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->orderBy('order', 'asc')->get();
    }

    public function findAllWithChildsAndReferencedBySuperMasterLabel()
    {
        return ModelsIndicator::with('childsHorizontalRecursive')->referenced()->rootHorizontal()->where(['label' => 'super-master'])->orderBy('order', 'asc')->get();
    }

    public function findAllWithTargetsAndRealizationsByLevelIdAndUnitIdAndYear(string|int $levelId, string|int|null $unitId = null, string|int $year)
    {
        return is_null($unitId) ?
        ModelsIndicator::with(['targets', 'realizations'])->where(['level_id' => $levelId, 'year' => $year])->orderBy('order', 'asc')->get() :
        ModelsIndicator::with(['targets', 'realizations'])->where(['level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->orderBy('order', 'asc')->get();
    }

    public function findAllWithChildsAndTargetsAndRealizationsByLevelIdAndUnitIdAndYear(string|int $levelId, string|int|null $unitId = null, string|int $year)
    {
        return is_null($unitId) ?
        ModelsIndicator::with(['targets', 'realizations', 'childsHorizontalRecursive'])->referenced()->rootHorizontal()->where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->orderBy('order', 'asc')->get() :
        ModelsIndicator::with(['targets', 'realizations', 'childsHorizontalRecursive'])->referenced()->rootHorizontal()->where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->orderBy('order', 'asc')->get();
    }

    public function findAllIdBySuperMasterLabel() : array
    {
        return ModelsIndicator::where(['label' => 'super-master'])->orderBy('order', 'asc')->get(['id'])->toArray();
    }

    public function findAllIdAndReferencedBySuperMasterLabel() : array
    {
        return ModelsIndicator::referenced()->where(['label' => 'super-master'])->orderBy('order', 'asc')->get(['id'])->toArray();
    }

    public function findAllWithParentsById(string|int $id) : array
    {
        return ModelsIndicatorOnlyId::with('parentHorizontalRecursive')->where(['id' => $id])->orderBy('order', 'asc')->get()->toArray();
    }

    public function findAllByIdList(array $idList)
    {
        return ModelsIndicator::whereIn('id', $idList)->orderBy('order', 'asc')->get();
    }

    public function findAllByLevelIdAndUnitIdAndYearAndIdList(array $idList, string|int $levelId, string|int|null $unitId = null, string|int $year)
    {
        return is_null($unitId) ?
        ModelsIndicator::where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->whereIn('id', $idList)->orderBy('order', 'asc')->get() :
        ModelsIndicator::where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->whereIn('id', $idList)->orderBy('order', 'asc')->get();
    }

    public function findAllByParentVerticalId(string|int $parentVerticalId)
    {
        return ModelsIndicator::where(['parent_vertical_id' => $parentVerticalId])->orderBy('order', 'asc')->get();
    }

    public function findAllByLevelIdAndUnitIdAndYear(string|int $levelId, string|int|null $unitId = null, string|int $year)
    {
        return is_null($unitId) ?
        ModelsIndicator::referenced()->where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->orderBy('order', 'asc')->get() :
        ModelsIndicator::referenced()->where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->orderBy('order', 'asc')->get();
    }

    public function findAllIdByLevelIdAndUnitIdAndYear(string|int|null $levelId = null, string|int|null $unitId = null, string|int|null $year = null) : array
    {
        if (is_null($levelId)) {
            return ModelsIndicatorOnlyId::referenced()->where(['label' => 'super-master'])->orderBy('order', 'asc')->get()->toArray();
        } else {
            if (is_null($unitId)) {
                return ModelsIndicatorOnlyId::referenced()->where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->orderBy('order', 'asc')->get()->toArray();
            } else {
                return ModelsIndicatorOnlyId::referenced()->where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->orderBy('order', 'asc')->get()->toArray();
            }
        }
    }

    public function findAllCodeByLevelIdAndUnitIdAndYear(string|int $levelId, string|int|null $unitId = null, string|int $year) : array
    {
        return is_null($unitId) ?
        ModelsIndicatorOnlyCode::referenced()->where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->orderBy('order', 'asc')->get()->toArray() :
        ModelsIndicatorOnlyCode::referenced()->where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->orderBy('order', 'asc')->get()->toArray();
    }
}
