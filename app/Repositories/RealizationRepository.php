<?php

namespace App\Repositories;

use App\Domains\Realization;
use App\Models\Realization as ModelsRealization;

class RealizationRepository {
    public function save(Realization $realization) : void
    {
        ModelsRealization::create([
            'id' => $realization->id,
            'indicator_id' => $realization->indicator_id,
            'month' => $realization->month,
            'value' => $realization->value,
            'locked' => $realization->locked,
            'default' => $realization->default,
        ]);
    }

    public function updateValueAndDefaultByMonthAndIndicatorId(string $month, string|int $indicatorId, float $value)
    {
        ModelsRealization::where(['indicator_id' => $indicatorId, 'month' => $month])->update(['locked' => true, 'default' => false, 'value' => $value]);
    }

    public function updateLockedByMonthAndIndicatorId(string $month, string|int $indicatorId, bool $locked)
    {
        ModelsRealization::where(['indicator_id' => $indicatorId, 'month' => $month])->update(['locked' => $locked]);
    }

    public function deleteById(string|int $id) : void
    {
        ModelsRealization::where(['id' => $id])->forceDelete();
    }

    public function deleteByMonthAndIndicatorId(string $month, string|int $indicatorId) : void
    {
        ModelsRealization::where(['indicator_id' => $indicatorId, 'month' => $month])->forceDelete();
    }

    public function deleteByIndicatorId(string|int $indicatorId) : void
    {
        ModelsRealization::where(['indicator_id' => $indicatorId])->forceDelete();
    }

    public function findByIndicatorIdAndMonth(string|int $indicatorId, string $month)
    {
        return ModelsRealization::firstWhere(['indicator_id' => $indicatorId, 'month' => $month]);
    }

    public function findAllByIndicatorId(string|int $indicatorId)
    {
        return ModelsRealization::where(['indicator_id' => $indicatorId])->get();
    }
}
