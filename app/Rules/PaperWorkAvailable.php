<?php

namespace App\Rules;

use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use Illuminate\Contracts\Validation\Rule;

class PaperWorkAvailable implements Rule
{
    private LevelRepository $levelRepository;
    private IndicatorRepository $indicatorRepository;
    private string $level;
    private string|int $year;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(string $level, string|int $year)
    {
        $this->levelRepository = new LevelRepository();
        $this->indicatorRepository = new IndicatorRepository();

        $this->level = $level;
        $this->year = $year;
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
        $sumOfIndicator = $this->indicatorRepository->countAllByLevelIdAndYear($this->levelRepository->findIdBySlug($this->level), $this->year);

        return $sumOfIndicator > 0 ? false : true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Kertas kerja KPI sudah tersedia !";
    }
}
