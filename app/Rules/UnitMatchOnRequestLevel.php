<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Unit;

class UnitMatchOnRequestLevel implements Rule
{
    private $level;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($level)
    {
        $this->level = $level;
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
        if ($this->level === 'super-master') {
            return true;
        } else if ($value === 'master') {
            return true;
        } else {
            $unit = Unit::with('level')->firstWhere(['slug' => $value]);
            return $unit->level->slug === $this->level ? true : false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return ":attribute tidak ditemukan atau tidak cocok dengan level terpilih.";
    }
}
