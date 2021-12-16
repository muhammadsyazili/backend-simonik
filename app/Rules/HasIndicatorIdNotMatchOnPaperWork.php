<?php

namespace App\Rules;

use App\Repositories\IndicatorRepository;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

class HasIndicatorIdNotMatchOnPaperWork implements Rule
{
    private IndicatorRepository $indicatorRepository;
    private array $indicators;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(array $indicators)
    {
        $this->indicatorRepository = new IndicatorRepository();

        $this->indicators = $indicators;
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
        $indicatorsId = Arr::flatten($this->indicatorRepository->findAllIdReferencedBySuperMasterLabel());

        foreach ($this->indicators as $k => $v) {
            if (!in_array($v, $indicatorsId)) {return false;}
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Terdapat ID indikator yang tidak sesuai dengan ID pada kertas kerja.';
    }
}
