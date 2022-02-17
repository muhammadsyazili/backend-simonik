<?php

namespace App\Repositories;

use App\Domains\User;
use App\Models\User as ModelsUser;

class UserRepository
{
    public function save(User $user): void
    {
        ModelsUser::create([
            'id' => $user->id,
            'nip' => $user->nip,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'actived' => $user->actived,
            'password' => $user->password,
            'unit_id' => $user->unit_id,
            'role_id' => $user->role_id,
        ]);
    }

    public function update__by__id(User $user)
    {
        ModelsUser::where(['id' => $user->id])->update([
            'nip' => $user->nip,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'unit_id' => $user->unit_id,
        ]);
    }

    public function count__all__by__username(string $username): int
    {
        return ModelsUser::where(['username' => $username])->count();
    }

    public function delete__by__id(string|int $id): void
    {
        ModelsUser::where(['id' => $id])->forceDelete();
    }

    public function find__by__id(string|int $id)
    {
        return ModelsUser::findOrFail($id);
    }

    public function find__with__role_unit_level__by__id(string|int $id)
    {
        return ModelsUser::with(['role', 'unit.level'])->findOrFail($id);
    }

    public function find__with__role__by__id(string|int $id)
    {
        return ModelsUser::with(['role'])->findOrFail($id);
    }

    public function find__all()
    {
        return ModelsUser::orderBy('role_id', 'asc')->orderBy('name', 'asc')->get();
    }

    public function find__all__with__role_unit_level()
    {
        return ModelsUser::with(['role', 'unit.level'])->orderBy('role_id', 'asc')->orderBy('name', 'asc')->get();
    }
}
