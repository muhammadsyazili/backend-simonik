<?php

namespace App\Repositories;

use Illuminate\Support\Arr;
use App\Models\Unit;
use App\Models\UnitOnlyId;
use App\Models\UnitOnlySlug;

class UnitRepository {
    public function count__all__by__slug(string $slug) : int
    {
        return Unit::where(['slug' => $slug])->count();
    }

    public function find__id__by__slug(string $slug) : string|int
    {
        return Unit::firstWhere(['slug' => $slug])->id;
    }

    public function find__all__with__indicator__by__levelId_year(string|int $levelId, string|int $year)
    {
        return Unit::with(['indicators' => function ($query) use ($year) {
            $query->where([
                'year' => $year,
            ]);
        }])->where(['level_id' => $levelId])->orderBy('name', 'asc')->get();
    }

    public function find__all__by__levelId(string|int $levelId)
    {
        return Unit::where(['level_id' => $levelId])->orderBy('name', 'asc')->get();
    }

    public function find__allSlug__with__this_childs__by__id(string|int $id) : array
    {
        $result = UnitOnlySlug::with('childsRecursive')->where(['id' => $id])->orderBy('name', 'asc')->get()->toArray();
        return Arr::flatten($result);
    }

    public function find__allSlug__with__childs__by__id(string|int $id) : array
    {
        $result = UnitOnlySlug::with('childsRecursive')->where(['parent_id' => $id])->orderBy('name', 'asc')->get()->toArray();
        return Arr::flatten($result);
    }

    public function find__allId__with__this_childs__by__id(string|int $id) : array
    {
        $result = UnitOnlyId::with('childsRecursive')->where(['id' => $id])->orderBy('name', 'asc')->get()->toArray();
        return Arr::flatten($result);
    }

    public function find__allId__with__childs__by__id(string|int $id) : array
    {
        $result = UnitOnlyId::with('childsRecursive')->where(['parent_id' => $id])->orderBy('name', 'asc')->get()->toArray();
        return Arr::flatten($result);
    }

    public function find__id__with__level__by__slug(string $slug)
    {
        return Unit::with('level')->firstWhere(['slug' => $slug]);
    }

    public function find__allSlug_allName__by__levelId(string|int $levelId)
    {
        return Unit::where(['level_id' => $levelId])->orderBy('name', 'asc')->get(['slug', 'name']);
    }

    public function find__all__with__level_parent()
    {
        return Unit::with(['level', 'parent'])->orderBy('name', 'asc')->get();
    }

    public function find__all()
    {
        return Unit::orderBy('name', 'asc')->get();
    }
}
