<?php

namespace App\Rules;

use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Contracts\Validation\Rule;

class IndicatorPaperWork__Available implements Rule
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
            $levelId = $this->levelRepository->find__id__by__slug($this->level);
            $sumOfIndicator = $this->unit === 'master' ? $this->indicatorRepository->count__all__by__levelId_unitId_year($levelId, null, $this->year) : $this->indicatorRepository->count__all__by__levelId_unitId_year($levelId, $this->unitRepository->find__id__by__slug($this->unit), $this->year);

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
