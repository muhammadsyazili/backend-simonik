<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;
use App\Models\UnitCustom;

class ValidRequestUnitBaseOnUserRole implements Rule
{
    public $user;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
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
            $childUnits = UnitCustom::with('childsRecursive')->where(['id' => $this->user->unit->id])->get()->toArray();
            return $value === 'master' || in_array($value, Arr::flatten($childUnits)) ? true : false;
        } else if ($this->user->role->name === 'data-entry' || $this->user->role->name === 'employee') {
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
        return "The filter :attribute doesn't match with your role.";
    }
}
