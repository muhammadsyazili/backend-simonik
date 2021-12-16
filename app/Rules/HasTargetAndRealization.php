<?php

namespace App\Rules;

use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Contracts\Validation\Rule;

class HasTargetAndRealization implements Rule
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
        $where = $this->unit === 'master' ? ['level_id' => $this->levelRepository->findIdBySlug($this->level), 'year' => $this->year] : ['level_id' => $this->levelRepository->findIdBySlug($this->level), 'unit_id' => $this->unitRepository->findIdBySlug($this->unit), 'year' => $this->year];

        $indicators = $this->indicatorRepository->findAllWithTargetsAndRealizationsByWhere($where);

        //cek apakah target or realization sudah ada yang di-edit
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
        return 'Kertas kerja tidak bisa dihapus, karena sudah memiliki target atau realisasi.';
    }
}
