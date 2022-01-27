<?php

namespace App\Rules;

use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Contracts\Validation\Rule;

class IndicatorPaperWorkAvailable implements Rule
{
    private LevelRepository $levelRepository;
    private UnitRepository $unitRepository;
    private IndicatorRepository $indicatorRepository;
    private string $level;
    private string|null $unit;
    private string|int|null $year;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(string $level, string|null $unit, string|int|null $year)
    {
        $this->levelRepository = new LevelRepository();
        $this->unitRepository = new UnitRepository();
        $this->indicatorRepository = new IndicatorRepository();

        $this->level = $level;
        $this->unit = $unit;
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
        if ($value === 'super-master') {
            return true;
        } else {
            $levelId = $this->levelRepository->findIdBySlug($this->level);
            $sumOfIndicator = $this->unit === 'master' ? $this->indicatorRepository->countAllByLevelIdAndUnitIdAndYear($levelId, null, $this->year) : $this->indicatorRepository->countAllByLevelIdAndUnitIdAndYear($levelId, $this->unitRepository->findIdBySlug($this->unit), $this->year);

            return $sumOfIndicator > 0 ? true : false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Kertas kerja KPI belum tersedia !";
    }
}
