<?php

namespace App\Repositories;

use App\Models\Unit;

class UnitRepository {
    public function findIdBySlug($slug)
    {
        Unit::firstWhere(['slug' => $slug])->id;
    }
}
