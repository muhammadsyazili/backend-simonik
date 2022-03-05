<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\User;

class Level__IsThisAndChildFromUser__Except__Admin_And_DataEntry_And_Employee implements Rule
{
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
        return "(#2.3) : Anda Tidak Memiliki Hak Akses !";
    }
}
