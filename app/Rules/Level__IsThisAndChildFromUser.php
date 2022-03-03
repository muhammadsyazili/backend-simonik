<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Repositories\LevelRepository;
use App\Models\User;

class Level__IsThisAndChildFromUser implements Rule
{
    private LevelRepository $levelRepository;
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
            return in_array($value, $this->levelRepository->find__allFlattenSlug__with__this_childs__by__id($this->user->unit->level->id)) ? true : false;
        } else if ($this->user->role->name === 'data-entry' || $this->user->role->name === 'employee') {
            return $value === $this->user->unit->level->slug ? true : false;
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
        return "(#2) : Anda Tidak Memiliki Hak Akses !";
    }
}
