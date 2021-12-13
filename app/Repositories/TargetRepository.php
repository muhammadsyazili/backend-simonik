<?php

namespace App\Repositories;

use App\Domains\Target;
use App\Models\Target as ModelsTarget;

class TargetRepository {
    public function save(Target $target) : void
    {
        ModelsTarget::create([
            'id' => $target->id,
            'indicator_id' => $target->indicator_id,
            'month' => $target->month,
            'value' => $target->value,
            'locked' => $target->locked,
            'default' => $target->default,
        ]);
    }
}
