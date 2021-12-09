<?php

namespace App\Repositories;

use App\Models\Unit;

class UnitRepository {
    public function findIdBySlug($slug)
    {
        return Unit::firstWhere(['slug' => $slug])->id;
    }

    public function findAllWithIndicatorByLevelId($level_id, $year)
    {
        return Unit::with(['indicators' => function ($query) use ($year) {
            $query->where([
                'year' => $year,
            ]);
        }])->where(['level_id' => $level_id])->get();
    }
}
