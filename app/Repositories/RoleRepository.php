<?php

namespace App\Repositories;

use App\Models\Role;

class RoleRepository {
    public function find__id__by__name(string $name) : string|int
    {
        return Role::firstWhere(['name' => $name])->id;
    }
}
