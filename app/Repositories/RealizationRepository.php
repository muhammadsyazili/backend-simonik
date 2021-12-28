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

    public function deleteById(string|int $id) : void
    {
        ModelsRealization::where(['id' => $id])->forceDelete();
    }

    public function deleteByMonthAndIndicatorId(string $month, string|int $indicatorId) : void
    {
        ModelsRealization::where(['indicator_id' => $indicatorId, 'month' => $month])->forceDelete();
    }

    public function findAllByIndicatorId(string|int $indicatorId)
    {
        return ModelsRealization::where(['indicator_id' => $indicatorId])->get();
    }
}
