<?php

namespace App\Repositories;

use App\Domains\Indicator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Models\Indicator as ModelsIndicator;
use App\Models\IndicatorOnlyId as ModelsIndicatorOnlyId;
use App\Models\IndicatorOnlyCode as ModelsIndicatorOnlyCode;

class IndicatorRepository
{
    public function save(Indicator $indicator): void
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
        $data['type'] = $indicator->type;
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

    public function update__by__id(Indicator $indicator): void
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
        $data['type'] = $indicator->type;
        $data['label'] = $indicator->label;
        $data['unit_id'] = $indicator->unit_id;
        $data['level_id'] = $indicator->level_id;
        $data['order'] = $indicator->order;
        $data['parent_vertical_id'] = $indicator->parent_vertical_id;
        $data['parent_horizontal_id'] = $indicator->parent_horizontal_id;

        $data['updated_at'] = \Carbon\Carbon::now();

        DB::table('indicators')->where(['id' => $indicator->id])->update($data);
    }

    public function count__allPlusOne__by__levelId_unitId_year(string|int $levelId, string|int|null $unitId = null, string|int|null $year = null): int
    {
        if ($levelId === 'super-master') {
            return ModelsIndicator::where(['label' => 'super-master'])->withTrashed()->count() + 1;
        } else {
            return is_null($unitId) ?
                ModelsIndicator::where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->count() + 1 :
                ModelsIndicator::where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->count() + 1;
        }
    }

    public function count__all__by__unitId(string|int $unitId): int
    {
        return ModelsIndicator::where(['unit_id' => $unitId])->count();
    }

    public function count__all__by__parentVerticalId_year(string|int $parentVerticalId, string|int $year): int
    {
        return ModelsIndicator::where(['parent_vertical_id' => $parentVerticalId, 'year' => $year])->count();
    }

    public function count__all__by__code(string|int $code): int
    {
        return ModelsIndicator::where(['code' => $code])->count();
    }

    public function count__all__by__idList_superMasterLabel(array $idList): int
    {
        return ModelsIndicator::where(['label' => 'super-master'])->whereIn('id', $idList)->count();
    }

    public function count__all__by__levelId_year(string|int $levelId, string|int $year): int
    {
        return ModelsIndicator::where(['level_id' => $levelId, 'year' => $year])->count();
    }

    public function count__all__by__levelId_unitId_year(string|int $levelId, string|int|null $unitId = null, string|int $year): int
    {
        return is_null($unitId) ?
            ModelsIndicator::where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->count() :
            ModelsIndicator::where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->count();
    }

    public function count__all__by__code_levelId_unitId_year(string|int $code, string|int $levelId, string|int|null $unitId = null, string|int $year): int
    {
        return is_null($unitId) ?
            ModelsIndicator::where(['label' => 'master', 'code' => $code, 'level_id' => $levelId, 'year' => $year])->count() :
            ModelsIndicator::where(['label' => 'child', 'code' => $code, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->count();
    }

    public function update__code__by__id(string|int $id): void
    {
        DB::table('indicators')->where(['id' => $id])->update(['code' => $id]);
    }

    public function update__order__by__id(int $order, string|int $id): void
    {
        ModelsIndicator::where(['id' => $id])->update(['order' => $order]);
    }

    public function update__order__by__parentVerticalId(int $order, string|int $parentVerticalId): void
    {
        ModelsIndicator::where(['parent_vertical_id' => $parentVerticalId])->update(['order' => $order]);
    }

    public function update__parentHorizontalId_referenced__by__id(string|int $id, string|int|null $parentHorizontalId = null): void
    {
        ModelsIndicator::where(['id' => $id])->update(['parent_horizontal_id' => $parentHorizontalId, 'referenced' => 1]);
    }

    public function delete__by__levelId_unitId_year(string|int $levelId, string|int|null $unitId = null, string|int $year): void
    {
        is_null($unitId) ?
            ModelsIndicator::where(['level_id' => $levelId, 'year' => $year])->forceDelete() :
            ModelsIndicator::where(['level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->forceDelete();
    }

    public function delete__by__id(string|int $id): void
    {
        ModelsIndicator::where(['id' => $id])->forceDelete();
    }

    public function find__by__id(string|int $id)
    {
        return ModelsIndicator::findOrFail($id);
    }

    public function find__label__by__id(string|int $id): string
    {
        return ModelsIndicator::firstWhere(['id' => $id])->label;
    }

    public function find__year__by__id(string|int $id): ?string
    {
        return ModelsIndicator::firstWhere(['id' => $id])->year;
    }

    public function find__by__code_levelId_unitId_year(string|int $code, string|int $levelId, string|int|null $unitId = null, string|int $year)
    {
        return is_null($unitId) ?
            ModelsIndicator::firstWhere(['label' => 'master', 'code' => $code, 'level_id' => $levelId, 'year' => $year]) :
            ModelsIndicator::firstWhere(['label' => 'child', 'code' => $code, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year]);
    }

    public function find__id__by__code_levelId_unitId_year(string|int $code, string|int $levelId, string|int|null $unitId = null, string|int $year): string|int
    {
        return is_null($unitId) ?
            ModelsIndicator::firstWhere(['label' => 'master', 'code' => $code, 'level_id' => $levelId, 'year' => $year])->id :
            ModelsIndicator::firstWhere(['label' => 'child', 'code' => $code, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->id;
    }

    public function find__with__level__by__id(string|int $id)
    {
        return ModelsIndicator::with('level')->findOrFail($id);
    }

    public function find__with__level_unit__by__id(string|int $id)
    {
        return ModelsIndicator::with(['level', 'unit'])->findOrFail($id);

    }

    public function find__with__targets_realizations__by__id(string|int $id)
    {
        return ModelsIndicator::with(['targets', 'realizations'])->findOrFail($id);
    }

    public function find__id_parentHorizontalId__by__label_levelId_unitId_year(string $label, string|int|null $levelId = null, string|int|null $unitId = null, string|int|null $year = null): array
    {
        return $label === 'super-master' ?
            ModelsIndicator::where(['label' => 'super-master'])->orderBy('type', 'asc')->orderBy('order', 'asc')->get(['id', 'parent_horizontal_id'])->toArray() :
            ModelsIndicator::where(['label' => $label, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->orderBy('type', 'asc')->orderBy('order', 'asc')->get(['id', 'parent_horizontal_id'])->toArray();
    }

    public function find__id__by__parentVerticalId_levelId_unitId_year(string|int $parentVerticalId, string|int $levelId, string|int $unitId, string|int $year): string|int
    {
        return ModelsIndicator::firstWhere(['parent_vertical_id' => $parentVerticalId, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->id;
    }

    public function find__allNotReferenced__by__superMasterLabel()
    {
        return ModelsIndicator::notReferenced()->where(['label' => 'super-master'])->orderBy('type', 'asc')->orderBy('order', 'asc')->get();
    }

    public function find__all__with__childs__by__superMasterLabel()
    {
        return ModelsIndicator::with('childsHorizontalRecursive')->rootHorizontal()->where(['label' => 'super-master'])->orderBy('type', 'asc')->orderBy('order', 'asc')->get();
    }

    public function find__allReferenced_rootHorizontal__with__childs__by__label_levelId_unitId_year(string $label, string|int|null $levelId = null, string|int|null $unitId = null, string|int|null $year = null)
    {
        return $label === 'super-master' ?
            ModelsIndicator::with('childsHorizontalRecursive')->referenced()->rootHorizontal()->where(['label' => 'super-master'])->orderBy('type', 'asc')->orderBy('order', 'asc')->get() :
            ModelsIndicator::with('childsHorizontalRecursive')->referenced()->rootHorizontal()->where(['label' => $label, 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->orderBy('type', 'asc')->orderBy('order', 'asc')->get();
    }

    public function find__all__with__childs_referenced__by__superMasterLabel()
    {
        return ModelsIndicator::with('childsHorizontalRecursive')->referenced()->rootHorizontal()->where(['label' => 'super-master'])->orderBy('type', 'asc')->orderBy('order', 'asc')->get();
    }

    public function find__all__with__targets_realizations__by__levelId_unitId_year(string|int $levelId, string|int|null $unitId = null, string|int $year)
    {
        return is_null($unitId) ?
            ModelsIndicator::with(['targets', 'realizations'])->where(['level_id' => $levelId, 'year' => $year])->orderBy('type', 'asc')->orderBy('order', 'asc')->get() :
            ModelsIndicator::with(['targets', 'realizations'])->where(['level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->orderBy('type', 'asc')->orderBy('order', 'asc')->get();
    }

    public function find__all__with__childs_targets_realizations__by__levelId_unitId_year(string|int $levelId, string|int|null $unitId = null, string|int $year)
    {
        return is_null($unitId) ?
            ModelsIndicator::with(['targets', 'realizations', 'childsHorizontalRecursive'])->referenced()->rootHorizontal()->where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->orderBy('type', 'asc')->orderBy('order', 'asc')->get() :
            ModelsIndicator::with(['targets', 'realizations', 'childsHorizontalRecursive'])->referenced()->rootHorizontal()->where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->orderBy('type', 'asc')->orderBy('order', 'asc')->get();
    }

    public function find__allId__by_SuperMasterLabel(): array
    {
        return ModelsIndicator::where(['label' => 'super-master'])->orderBy('type', 'asc')->orderBy('order', 'asc')->get(['id'])->toArray();
    }

    public function find__allId_referenced__by__superMasterLabel(): array
    {
        return ModelsIndicator::referenced()->where(['label' => 'super-master'])->orderBy('type', 'asc')->orderBy('order', 'asc')->get(['id'])->toArray();
    }

    public function find__all__with__parents__by__id(string|int $id): array
    {
        $result = ModelsIndicatorOnlyId::with('parentHorizontalRecursive')->where(['id' => $id])->orderBy('type', 'asc')->orderBy('order', 'asc')->get()->toArray();
        return Arr::flatten($result);
    }

    public function find__all__by__idList(array $idList)
    {
        return ModelsIndicator::whereIn('id', $idList)->orderBy('type', 'asc')->orderBy('order', 'asc')->get();
    }

    public function find__all__by__idList_levelId_unitId_year(array $idList, string|int $levelId, string|int|null $unitId = null, string|int $year)
    {
        return is_null($unitId) ?
            ModelsIndicator::where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->whereIn('id', $idList)->orderBy('type', 'asc')->orderBy('order', 'asc')->get() :
            ModelsIndicator::where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->whereIn('id', $idList)->orderBy('type', 'asc')->orderBy('order', 'asc')->get();
    }

    public function findAllByParentVerticalId(string|int $parentVerticalId)
    {
        return ModelsIndicator::where(['parent_vertical_id' => $parentVerticalId])->orderBy('type', 'asc')->orderBy('order', 'asc')->get();
    }

    public function find__all__by__levelId_unitId_year(string|int $levelId, string|int|null $unitId = null, string|int $year)
    {
        return is_null($unitId) ?
            ModelsIndicator::referenced()->where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->orderBy('type', 'asc')->orderBy('order', 'asc')->get() :
            ModelsIndicator::referenced()->where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->orderBy('type', 'asc')->orderBy('order', 'asc')->get();
    }

    public function find__allId__by__levelId_unitId_year(string|int|null $levelId = null, string|int|null $unitId = null, string|int|null $year = null): array
    {
        $result = null;
        if (is_null($levelId)) {
            $result = ModelsIndicatorOnlyId::referenced()->where(['label' => 'super-master'])->orderBy('type', 'asc')->orderBy('order', 'asc')->get()->toArray();
        } else {
            if (is_null($unitId)) {
                $result = ModelsIndicatorOnlyId::referenced()->where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->orderBy('type', 'asc')->orderBy('order', 'asc')->get()->toArray();
            } else {
                $result = ModelsIndicatorOnlyId::referenced()->where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->orderBy('type', 'asc')->orderBy('order', 'asc')->get()->toArray();
            }
        }

        return Arr::flatten($result);
    }

    public function find__allCode__by__levelId_unitId_year(string|int $levelId, string|int|null $unitId = null, string|int $year): array
    {
        $result = is_null($unitId) ?
            ModelsIndicatorOnlyCode::referenced()->where(['label' => 'master', 'level_id' => $levelId, 'year' => $year])->orderBy('type', 'asc')->orderBy('order', 'asc')->get()->toArray() :
            ModelsIndicatorOnlyCode::referenced()->where(['label' => 'child', 'level_id' => $levelId, 'unit_id' => $unitId, 'year' => $year])->orderBy('type', 'asc')->orderBy('order', 'asc')->get()->toArray();

        return Arr::flatten($result);
    }
}
