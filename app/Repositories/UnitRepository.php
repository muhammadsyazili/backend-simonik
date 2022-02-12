<?php

namespace App\Repositories;

use App\Domains\Unit;
use Illuminate\Support\Arr;
use App\Models\Unit as ModelsUnit;
use App\Models\UnitOnlyId as ModelsUnitOnlyId;
use App\Models\UnitOnlySlug as ModelsUnitOnlySlug;

class UnitRepository
{
    public function update__name_slug__by__id(Unit $unit, string|int $id)
    {
        ModelsUnit::where(['id' => $id])->update([
            'name' => $unit->name,
            'slug' => $unit->slug,
        ]);
    }

    public function count__all__by__slug(string $slug): int
    {
        return ModelsUnit::where(['slug' => $slug])->count();
    }

    public function count__all__by__levelId(string|int $levelId): int
    {
        return ModelsUnit::where(['level_id' => $levelId])->count();
    }

    public function find__id__by__slug(string $slug): string|int
    {
        return ModelsUnit::firstWhere(['slug' => $slug])->id;
    }

    public function find__all__with__indicator__by__levelId_year(string|int $levelId, string|int $year)
    {
        return ModelsUnit::with(['indicators' => function ($query) use ($year) {
            $query->where([
                'year' => $year,
            ]);
        }])->where(['level_id' => $levelId])->orderBy('name', 'asc')->get();
    }

    public function find__all__by__levelId(string|int $levelId)
    {
        return ModelsUnit::where(['level_id' => $levelId])->orderBy('name', 'asc')->get();
    }

    public function find__allSlug__with__this_childs__by__id(string|int $id): array
    {
        $result = ModelsUnitOnlySlug::with('childsRecursive')->where(['id' => $id])->orderBy('name', 'asc')->get()->toArray();
        return Arr::flatten($result);
    }

    public function find__allSlug__with__childs__by__id(string|int $id): array
    {
        $result = ModelsUnitOnlySlug::with('childsRecursive')->where(['parent_id' => $id])->orderBy('name', 'asc')->get()->toArray();
        return Arr::flatten($result);
    }

    public function find__allId__with__this_childs__by__id(string|int $id): array
    {
        $result = ModelsUnitOnlyId::with('childsRecursive')->where(['id' => $id])->orderBy('name', 'asc')->get()->toArray();
        return Arr::flatten($result);
    }

    public function find__allId__with__childs__by__id(string|int $id): array
    {
        $result = ModelsUnitOnlyId::with('childsRecursive')->where(['parent_id' => $id])->orderBy('name', 'asc')->get()->toArray();
        return Arr::flatten($result);
    }

    public function find__id__with__level__by__slug(string $slug)
    {
        return ModelsUnit::with('level')->firstWhere(['slug' => $slug]);
    }

    public function find__allSlug_allName__by__levelId(string|int $levelId)
    {
        return ModelsUnit::where(['level_id' => $levelId])->orderBy('name', 'asc')->get(['slug', 'name']);
    }

    public function find__all__with__level_parent()
    {
        return ModelsUnit::with(['level', 'parent'])->orderBy('name', 'asc')->get();
    }

    public function find__all()
    {
        return ModelsUnit::orderBy('name', 'asc')->get();
    }
}
