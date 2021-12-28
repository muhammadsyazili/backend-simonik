<?php

namespace App\Rules;

use App\Repositories\IndicatorRepository;
use Illuminate\Contracts\Validation\Rule;

class IsExtentionNotSuperMaster implements Rule
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
        if ($this->indicatorRepository->findLabelColumnById($value) === 'super-master') {
            return $this->indicatorRepository->countCodeColumnById($value) > 1 ? false : true;
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
        return "Indikator tidak bisa dihapus.";
    }
}
