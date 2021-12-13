<?php

namespace App\Repositories;

use App\Models\Unit;

class UnitRepository {
    public function findIdBySlug(string $slug) : string|int
    {
        return Unit::firstWhere(['slug' => $slug])->id;
    }

    public function findAllWithIndicatorByLevelId(string|int $level_id, string $year)
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
}
