<?php

namespace App\Repositories;

use App\Models\Unit;
use App\Models\UnitOnlySlug;

class UnitRepository {
    public function findIdBySlug(string $slug) : string|int
    {
        return Unit::firstWhere(['slug' => $slug])->id;
    }

    public function findAllWithIndicatorByLevelIdAndYear(string|int $level_id, string|int $year)
    {
        return Unit::with(['indicators' => function ($query) use ($year) {
            $query->where([
                'year' => $year,
            ]);
        }])->where(['level_id' => $level_id])->get();
    }

    public function findAllByLevelId(string|int $level_id)
    {
        return Unit::where(['level_id' => $level_id])->get();
    }

    public function findAllSlugWithChildsById(string|int $id) : array
    {
        return UnitOnlySlug::with('childsRecursive')->where(['id' => $id])->get()->toArray();
    }

    public function findIdWithLevelBySlug(string $slug)
    {
        return Unit::with('level')->firstWhere(['slug' => $slug]);
    }
}
