<?php

namespace App\Repositories;

use App\Models\Level;
use App\Models\LevelOnlySlug;

class LevelRepository {
    public function findIdBySlug(string $slug) : string|int
    {
        return Level::firstWhere(['slug' => $slug])->id;
    }

    public function findAllSlugWithChildsByRoot() : array
    {
        return LevelOnlySlug::with('childsRecursive')->whereNull('parent_id')->get()->toArray();
    }

    public function findAllSlugWithChildsById(string|int $id) : array
    {
        return LevelOnlySlug::with('childsRecursive')->where(['id' => $id])->get()->toArray();
    }

    public function findAllWithChildsByRoot()
    {
        return Level::with('childsRecursive')->whereNull('parent_id')->get();
    }

    public function findAllWithChildsById(string|int $id)
    {
        return Level::with('childsRecursive')->where(['id' => $id])->get();
    }

    public function findAllIdByRoot() : array
    {
        return Level::whereNull('parent_id')->get(['id'])->toArray();
    }

    public function findAllIdById(string|int $id) : array
    {
        return Level::where(['id' => $id])->get(['id'])->toArray();
    }

    public function findAllWithChildsByParentId(array $parent_id)
    {
        return Level::with('childsRecursive')->whereIn('parent_id', $parent_id)->get();
    }
}
