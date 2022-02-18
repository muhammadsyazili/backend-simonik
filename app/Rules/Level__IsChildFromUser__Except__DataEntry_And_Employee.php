<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;
use App\Repositories\LevelRepository;

class Level__IsChildFromUser__Except__DataEntry_And_Employee implements Rule
{
    private LevelRepository $levelRepository;
    private $user;

    /**
     * Create a new rule instance.
     *
     * @param mixed $user
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;

        $this->levelRepository = new LevelRepository();
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
        } else if ($this->user->role->name === 'admin') {
            return in_array($value, $this->levelRepository->find__allFlattenSlug__with__childs__by__id($this->user->unit->level->id)) ? true : false;
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
        return "(#1.1) : Anda Tidak Memiliki Hak Akses !";
    }
}
