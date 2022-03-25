<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Repositories\UnitRepository;
use App\Models\User;

class Unit__IsThisAndChildFromUser__Custom implements Rule
{
    private UnitRepository $unitRepository;
    private User $user;

    /**
     * Create a new rule instance.
     *
     * @param mixed $user
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;

        $this->unitRepository = new UnitRepository();
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($this->user->role->name === 'super-admin') {
            return true;
        } else if ($this->user->role->name === 'admin' || $this->user->role->name === 'data-entry' || $this->user->role->name === 'employee') {
            return $value === 'master' || in_array($value, $this->unitRepository->find__allFlattenSlug__with__this_childs__by__id($this->user->unit->id)) ? true : false;
        } else {
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "(#4.5) : Anda Tidak Memiliki Hak Akses !";
    }
}
