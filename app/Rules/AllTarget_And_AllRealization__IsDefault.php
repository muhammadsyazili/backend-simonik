<?php

namespace App\Rules;

use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Contracts\Validation\Rule;
use App\Models\User;

//Terdapat KPI yang sudah punya kertas kerja target & realisasi
class AllTarget_And_AllRealization__IsDefault implements Rule
{
    private IndicatorRepository $indicatorRepository;
    private LevelRepository $levelRepository;
    private UnitRepository $unitRepository;

    private string $unit;
    private string $level;
    private string|int $year;
    private User $user;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(User $user, string $level, string $unit, string|int $year)
    {
        $this->indicatorRepository = new IndicatorRepository();
        $this->levelRepository = new LevelRepository();
        $this->unitRepository = new UnitRepository();

        $this->unit = $unit;
        $this->level = $level;
        $this->year = $year;
        $this->user = $user;
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
        if ($this->user->role->name === 'super-admin') {
            return true;
        }

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

            if ($isDefault === false) {
                break;
            }

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
        return 'Terdapat Target/Realisasi Yang Suadah Pernah Diubah, Jika Ingin Tetap Menghapus Kurang Dari Tahun Sekarang Silakkan Hubungi Super Admin !';
    }
}
