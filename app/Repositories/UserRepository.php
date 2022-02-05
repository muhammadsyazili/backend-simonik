<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository {
    public function find__with__role_unit_level__by__id(string|int $id)
    {
        return User::with(['role', 'unit.level'])->findOrFail($id);
    }

    public function find__with__role__by__id(string|int $id)
    {
        return User::with(['role'])->findOrFail($id);
    }
}
