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

    public function update__value_default__by__month_indicatorId(string $month, string|int $indicatorId, float $value)
    {
        ModelsTarget::where(['indicator_id' => $indicatorId, 'month' => $month])->update(['default' => false, 'value' => $value]);
    }

    public function delete__by__id(string|int $id) : void
    {
        ModelsTarget::where(['id' => $id])->forceDelete();
    }

    public function delete__by__month_indicatorId(string $month, string|int $indicatorId) : void
    {
        ModelsTarget::where(['indicator_id' => $indicatorId, 'month' => $month])->forceDelete();
    }

    public function delete__by__indicatorId(string|int $indicatorId) : void
    {
        ModelsTarget::where(['indicator_id' => $indicatorId])->forceDelete();
    }

    public function find__by__indicatorId_month(string|int $indicatorId, string $month)
    {
        return ModelsTarget::firstWhere(['indicator_id' => $indicatorId, 'month' => $month]);
    }

    public function find__all__by__indicatorId(string|int $indicatorId)
    {
        return ModelsTarget::where(['indicator_id' => $indicatorId])->get();
    }
}
