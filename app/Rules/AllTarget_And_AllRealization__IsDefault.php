<?php

namespace App\Rules;

use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Contracts\Validation\Rule;

//Terdapat KPI yang sudah punya kertas kerja target & realisasi
class AllTarget_And_AllRealization__IsDefault implements Rule
{
    private IndicatorRepository $indicatorRepository;
    private LevelRepository $levelRepository;
    private UnitRepository $unitRepository;

    private string $unit;
    private string $level;
    private string|int $year;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(string $level, string $unit, string|int $year)
    {
        $this->indicatorRepository = new IndicatorRepository();
        $this->levelRepository = new LevelRepository();
        $this->unitRepository = new UnitRepository();

        $this->unit = $unit;
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
        $levelId = $this->levelRepository->find__id__by__slug($this->level);
        $indicators = $this->unit === 'master' ? $this->indicatorRepository->find__all__with__targets_realizations__by__levelId_unitId_year($levelId, null, $this->year) : $this->indicatorRepository->find__all__with__targets_realizations__by__levelId_unitId_year($levelId, $this->unitRepository->find__id__by__slug($this->unit), $this->year);

        //cek apakah target or realisasi sudah ada yang di-edit
        $isDefault = true;
        foreach ($indicators as $indicator) {
            foreach ($indicator->targets as $target) {
                if (!$target->default) {
                    $isDefault = false;
                    break;
                }
            }

            if ($isDefault === false) {break;}

            foreach ($indicator->realizations as $realization) {
                if (!$realization->default) {
                    $isDefault = false;
                    break;
                }
            }
        }

        return $isDefault ? true : false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Terdapat Target/Realisasi Berstatus Un-default !';
    }
}
