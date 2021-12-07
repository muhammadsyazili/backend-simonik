<?php

namespace App\Repositories;

use App\Models\Level;

class LevelRepository {
    public function findIdBySlug($slug)
    {
        return Level::firstWhere(['slug' => $slug])->id;
    }
}
