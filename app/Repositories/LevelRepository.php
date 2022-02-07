<?php

namespace App\Repositories;

use App\Models\Level;
use App\Models\LevelOnlyId;
use App\Models\LevelOnlySlug;

class LevelRepository {
    public function find__id__by__slug(string $slug) : string|int
    {
        return Level::firstWhere(['slug' => $slug])->id;
    }

    public function find__allSlug__with__childs__by__root() : array
    {
        return LevelOnlySlug::with('childsRecursive')->whereNull('parent_id')->orderBy('name', 'asc')->get()->toArray();
    }

    public function find__allSlug__with__this_childs__by__id(string|int $id) : array
    {
        return LevelOnlySlug::with('childsRecursive')->where(['id' => $id])->orderBy('name', 'asc')->get()->toArray();
    }

    public function find__allSlug__with__childs__by__id(string|int $id) : array
    {
        return LevelOnlySlug::with('childsRecursive')->where(['parent_id' => $id])->orderBy('name', 'asc')->get()->toArray();
    }

    public function find__allId__with__this_childs__by__id(string|int $id) : array
    {
        return LevelOnlyId::with('childsRecursive')->where(['id' => $id])->orderBy('name', 'asc')->get()->toArray();
    }

    public function find__allId__with__childs__by__id(string|int $id) : array
    {
        return LevelOnlyId::with('childsRecursive')->where(['parent_id' => $id])->orderBy('name', 'asc')->get()->toArray();
    }

    public function find__all__with__childs__by__root()
    {
        return Level::with('childsRecursive')->whereNull('parent_id')->orderBy('name', 'asc')->get();
    }

    public function find__all__with__childs__by__id(string|int $id)
    {
        return Level::with('childsRecursive')->where(['id' => $id])->orderBy('name', 'asc')->get();
    }

    public function find__allId__by__root() : array
    {
        return Level::whereNull('parent_id')->orderBy('name', 'asc')->get(['id'])->toArray();
    }

    public function find__allId__by__id(string|int $id) : array
    {
        return Level::where(['id' => $id])->orderBy('name', 'asc')->get(['id'])->toArray();
    }

    public function find__all__with__childs__by__parentIdList(array $parentIdList)
    {
        return Level::with('childsRecursive')->whereIn('parent_id', $parentIdList)->orderBy('name', 'asc')->get();
    }

    public function find__all__with__childs__by__parentId(string|int $parentId)
    {
        return Level::with('childsRecursive')->where(['parent_id' => $parentId])->orderBy('name', 'asc')->get();
    }

    public function find__all__with__parent()
    {
        return Level::with('parent')->orderBy('parent_id', 'asc')->orderBy('name', 'asc')->get();
    }
}
