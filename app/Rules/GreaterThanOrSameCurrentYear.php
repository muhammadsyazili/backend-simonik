<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\User;

class GreaterThanOrSameCurrentYear implements Rule
{
    private User $user;
    private string|int|null $year;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(User $user, string|int|null $year = null)
    {
        $this->user = $user;
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
        if ($this->user->role->name === 'super-admin') {
            return true;
        } else {
            if ($this->user->role->name === 'admin') {
                $year = is_null($this->year) ? $value : $this->year;
                if ((int) $year >= (int) now()->year) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Tahun Yang Dipilih Tidak Boleh Kurang Dari Tahun Sekarang, Jika Ingin Tetap Melakukan Aksi Tersebut Silakkan Hubungi Super Admin !';
    }
}
