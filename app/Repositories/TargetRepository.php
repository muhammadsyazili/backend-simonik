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

    public function deleteById(string|int $id) : void
    {
        ModelsTarget::where(['id' => $id])->forceDelete();
    }

    public function deleteByMonthAndIndicatorId(string $month, string|int $indicatorId) : void
    {
        ModelsTarget::where(['indicator_id' => $indicatorId, 'month' => $month])->forceDelete();
    }

    public function deleteByIndicatorId(string|int $indicatorId) : void
    {
        ModelsTarget::where(['indicator_id' => $indicatorId])->forceDelete();
    }

    public function findAllByIndicatorId(string|int $indicatorId)
    {
        return ModelsTarget::where(['indicator_id' => $indicatorId])->get();
    }
}
