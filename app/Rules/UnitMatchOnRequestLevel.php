<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Repositories\UnitRepository;

class UnitMatchOnRequestLevel implements Rule
{
    private UnitRepository $unitRepository;
    private $level;

    /**
     * Create a new rule instance.
     *
     * @param mixed $level
     * @return void
     */
    public function __construct($level)
    {
        $this->level = $level;

        $this->unitRepository = new UnitRepository();
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
        if ($this->level === 'super-master') {
            return true;
        } else if ($value === 'master') {
            return true;
        } else {
            $unit = $this->unitRepository->findIdWithLevelBySlug($value);
            return $unit->level->slug === $this->level ? true : false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Anda tidak memiliki hak akses. (#VW5pdE1hdGNoT25SZXF1ZXN0TGV2ZWw)";
    }
}
