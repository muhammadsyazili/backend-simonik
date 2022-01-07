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

    public function countOrderColumn(string|int $levelId, string|int|null $unitId = null, string|null $year = null) : int
    {
        if ($levelId === 'super-master') {
            return ModelsIndicator::where(['label' => 'super-master'])->withTrashed()->count()+1;
        } else {
            if (is_null($unitId)) {
                return ModelsIndicator::where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->count()+1;
            } else {
                return ModelsIndicator::where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->count()+1;
            }
        }
    }

    public function countCodeColumnById(string|int $id) : int
    {
        return ModelsIndicator::where(['code' => $id])->count();
    }

    public function countByIdListAndSuperMasterLabel(array $idList) : int
    {
        return ModelsIndicator::where(['label' => 'super-master'])->whereIn('id', $idList)->count();
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

    public function deleteById(string|int $id) : void
    {
        ModelsIndicator::where(['id' => $id])->forceDelete();
    }

    public function findById(string|int $id)
    {
        return ModelsIndicator::findOrFail($id);
    }

    public function findLabelColumnById(string|int $id) : string
    {
        return ModelsIndicator::firstWhere(['id' => $id])->label;
    }

    public function findCodeColumnById(string|int $id) : string|int
    {
        return ModelsIndicator::firstWhere(['id' => $id])->code;
    }

    public function findIdColumnByCodeMaster(string|int $code, string|int $levelId, string $year) : string|int
    {
        return ModelsIndicator::firstWhere(['label' => 'master', 'code' => $code, 'level_id' => $levelId, 'year' => $year])->id;
    }

    public function findIdColumnByCodeChild(string|int $code, string|int $levelId, string|int $unitId, string $year) : string|int
    {
        return ModelsIndicator::firstWhere(['label' => 'child', 'code' => $code, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->id;
    }

    public function findAllByCodeAndLevelIdAndUnitIdAndYear(string|int $code, string|int $levelId, string|int|null $unitId, string $year)
    {
        return is_null($unitId) ?
        ModelsIndicator::with(['targets'])->firstWhere(['label' => 'master', 'code' => $code, 'level_id' => $levelId, 'year' => $year]) :
        ModelsIndicator::with(['targets', 'realizations'])->firstWhere(['label' => 'child', 'code' => $code, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year]);
    }

    public function findCodeByIdAndLevelIdAndUnitIdAndYear(string|int $id, string|int $levelId, string|int|null $unitId, string $year)
    {
        return is_null($unitId) ?
        ModelsIndicator::firstWhere(['label' => 'master', 'id' => $id, 'level_id' => $levelId, 'year' => $year])->code :
        ModelsIndicator::firstWhere(['label' => 'child', 'id' => $id, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->code;
    }

    public function findIdByCodeAndLevelIdAndYear(string|int $code, string|int $levelId, string $year) : string|int
    {
        return ModelsIndicator::firstWhere(['label' => 'master', 'code' => $code, 'level_id' => $levelId, 'year' => $year])->id;
    }

    public function findByCodeAndLevelIdAndYear(string|int $code, string|int $levelId, string $year)
    {
        return ModelsIndicator::firstWhere(['label' => 'master', 'code' => $code, 'level_id' => $levelId, 'year' => $year]);
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

    public function findAllByIdList(array $idList)
    {
        return ModelsIndicator::whereIn('id', $idList)->get();
    }

    public function findIdByCodeList(array $codeList, string|int $levelId, string|int|null $unitId, string $year)
    {
        return ModelsIndicator::where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->whereIn('code', $codeList)->get(['id']);
    }

    public function findAllByWhere(array $where)
    {
        return ModelsIndicator::where($where)->get();
    }

    public function findAllByParentVerticalId(string|int $parentVerticalId)
    {
        return ModelsIndicator::where(['parent_vertical_id' => $parentVerticalId])->get();
    }

    public function findAllByLevelIdAndUnitIdAndYear(string|int $levelId, string|int|null $unitId, string $year)
    {
        return is_null($unitId) ?
        ModelsIndicator::referenced()->where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->get() :
        ModelsIndicator::referenced()->where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->get();
    }

    public function findAllCodeByLevelIdAndUnitIdAndYear(string|int $levelId, string|int|null $unitId, string $year)
    {
        return is_null($unitId) ?
        ModelsIndicator::referenced()->where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->get(['id', 'code']) :
        ModelsIndicator::referenced()->where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->get(['id', 'code']);
    }

    public function findAllIdIsByLevelIdAndUnitIdAndYear(string|int $levelId, string|int|null $unitId, string $year) : array
    {
        return is_null($unitId) ?
        ModelsIndicatorOnlyId::referenced()->where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->get()->toArray() :
        ModelsIndicatorOnlyId::referenced()->where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->get()->toArray();
    }

    public function findAllCodeIsByLevelIdAndUnitIdAndYear(string|int $levelId, string|int|null $unitId, string $year) : array
    {
        return is_null($unitId) ?
        ModelsIndicatorOnlyCode::referenced()->where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->get()->toArray() :
        ModelsIndicatorOnlyCode::referenced()->where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->get()->toArray();
    }
}
