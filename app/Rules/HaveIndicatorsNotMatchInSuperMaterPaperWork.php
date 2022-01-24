<?php

namespace App\Rules;

use App\Repositories\IndicatorRepository;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

//Terdapat KPI yang tidak cocok dengan kertas kerja 'super-master'
class HaveIndicatorsNotMatchInSuperMaterPaperWork implements Rule
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
        $indicators = Arr::flatten($this->indicatorRepository->findAllIdAndReferencedBySuperMasterLabel());

        foreach ($this->indicators as $indicator) {
            if (!in_array($indicator, $indicators)) {return false;}
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
        return 'Terdapat KPI yang tidak sesuai dengan kertas kerja KPI seharusnya !';
    }
}
