<?php

namespace App\Rules;

use App\Repositories\IndicatorRepository;
use Illuminate\Contracts\Validation\Rule;

//KPI tidak berlabel 'super-master'
class Indicator__IsSuperMaster implements Rule
{
    private IndicatorRepository $indicatorRepository;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->indicatorRepository = new IndicatorRepository();
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
        return $this->indicatorRepository->find__label__by__id($value) === 'super-master' ? true : false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "(#1) : Kpi Tidak Bisa Dihapus !";
    }
}
