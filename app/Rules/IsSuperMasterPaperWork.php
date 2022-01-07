<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

//Merupakan kertas kerja 'super-master'
class IsSuperMasterPaperWork implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        return $value === 'super-master' ? false : true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Anda tidak memiliki hak akses. (070ea66524fec89074ec95d24c427a21)";
    }
}
