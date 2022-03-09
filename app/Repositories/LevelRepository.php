<?php

namespace App\Repositories;

use App\Domains\Level;
use Illuminate\Support\Arr;
use App\Models\Level as ModelsLevel;
use App\Models\LevelOnlyId as ModelsLevelOnlyId;
use App\Models\LevelOnlySlug as ModelsLevelOnlySlug;

class LevelRepository
{
    public function save(Level $level): void
    {
        ModelsLevel::create([
            'name' => $level->name,
            'slug' => $level->slug,
            'parent_id' => $level->parent_id,
        ]);
    }

    public function update__by__id(Level $level)
    {
        ModelsLevel::where(['id' => $level->id])->update([
            'name' => $level->name,
            'slug' => $level->slug,
            'parent_id' => $level->parent_id,
        ]);
    }

    public function count__all__by__slug(string $slug): int
    {
        return ModelsLevel::where(['slug' => $slug])->count();
    }

    public function delete__by__id(string|int $id): void
    {
        ModelsLevel::where(['id' => $id])->forceDelete();
    }

    public function find__by__id(string|int $id)
    {
        return ModelsLevel::findOrFail($id);
    }

    public function find__id__by__slug(string $slug): string|int
    {
        return ModelsLevel::firstWhere(['slug' => $slug])->id;
    }

    public function find__with__parent__by__slug(string $slug)
    {
        return ModelsLevel::with('parent')->firstWhere(['slug' => $slug]);
    }

    public function find__allSlug__with__childs__by__root(): array
    {
        $result = ModelsLevelOnlySlug::with('childsRecursive')->whereNull('parent_id')->orderBy('name', 'asc')->get()->toArray();
        return Arr::flatten($result);
    }

    public function find__allFlattenSlug__with__this_childs__by__id(string|int $id): array
    {
        $result = ModelsLevelOnlySlug::with('childsRecursive')->where(['id' => $id])->orderBy('name', 'asc')->get()->toArray();
        return Arr::flatten($result);
    }

    public function find__allFlattenSlug__with__childs__by__id(string|int $id): array
    {
        $result = ModelsLevelOnlySlug::with('childsRecursive')->where(['parent_id' => $id])->orderBy('name', 'asc')->get()->toArray();
        return Arr::flatten($result);
    }

    public function find__allFlattenId__with__this_childs__by__id(string|int $id): array
    {
        $result = ModelsLevelOnlyId::with('childsRecursive')->where(['id' => $id])->orderBy('name', 'asc')->get()->toArray();
        return Arr::flatten($result);
    }

    public function find__allFlattenId__with__childs__by__id(string|int $id): array
    {
        $result = ModelsLevelOnlyId::with('childsRecursive')->where(['parent_id' => $id])->orderBy('name', 'asc')->get()->toArray();
        return Arr::flatten($result);
    }

    public function find__all__with__childs__by__root()
    {
        return ModelsLevel::with('childsRecursive')->whereNull('parent_id')->orderBy('name', 'asc')->get();
    }

    public function find__all__with__childs__by__id(string|int $id)
    {
        return ModelsLevel::with('childsRecursive')->where(['id' => $id])->orderBy('name', 'asc')->get();
    }

    public function find__allId__by__root(): array
    {
        return ModelsLevel::whereNull('parent_id')->orderBy('name', 'asc')->get(['id'])->toArray();
    }

    public function find__allId__by__id(string|int $id): array
    {
        return ModelsLevel::where(['id' => $id])->orderBy('name', 'asc')->get(['id'])->toArray();
    }

    public function find__all__with__childs__by__parentIdList(array $parentIdList)
    {
        return ModelsLevel::with('childsRecursive')->whereIn('parent_id', $parentIdList)->orderBy('name', 'asc')->get();
    }

    public function find__all__with__childs__by__parentId(string|int $parentId)
    {
        return ModelsLevel::with('childsRecursive')->where(['parent_id' => $parentId])->orderBy('name', 'asc')->get();
    }

    public function find__all__with__parent()
    {
        return ModelsLevel::with('parent')->orderBy('parent_id', 'asc')->orderBy('name', 'asc')->get();
    }

    public function find__all()
    {
        return ModelsLevel::orderBy('parent_id', 'asc')->orderBy('name', 'asc')->get();
    }

    public function find__all__not__superMaster()
    {
        return ModelsLevel::whereNotIn('slug', ['super-master'])->orderBy('parent_id', 'asc')->orderBy('name', 'asc')->get();
    }
}
