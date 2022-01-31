<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;
use App\Repositories\UnitRepository;

class UnitIsThisAndChildUser__Except__Employee implements Rule
{
    private UnitRepository $unitRepository;
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
        } else if ($this->user->role->name === 'admin') {
            return $value === 'master' || in_array($value, Arr::flatten($this->unitRepository->findAllSlugWithChildsById($this->user->unit->id))) ? true : false;
        } else if ($this->user->role->name === 'data-entry') {
            return $value === $this->user->unit->slug ? true : false;
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
        return "(#4.2) : Anda tidak memiliki hak akses !";
    }
}
