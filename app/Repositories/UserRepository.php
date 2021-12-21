<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository {
    public function findWithRoleUnitLevelById(string|int $id)
    {
        return User::with(['role', 'unit.level'])->findOrFail($id);
    }

    public function findWithRoleById(string|int $id)
    {
        return User::with(['role'])->findOrFail($id);
    }
}
