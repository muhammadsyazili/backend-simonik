<?php

namespace App\Repositories;

use App\Models\Role as ModelsRole;

class RoleRepository
{
    public function find__id__by__name(string $name): string|int
    {
        return ModelsRole::firstWhere(['name' => $name])->id;
    }
}
