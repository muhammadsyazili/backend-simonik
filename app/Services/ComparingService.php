<?php

namespace App\Services;

use App\DTO\ComparingComparingRequest;
use App\DTO\ComparingComparingResponse;
use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;

class ComparingService
{
    private ?IndicatorRepository $indicatorRepository;

    private int $decimals_showed = 2;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->indicatorRepository = $constructRequest->indicatorRepository;
    }

    //use repo IndicatorRepository
    public function comparing(ComparingComparingRequest $comparingRequest): ComparingComparingResponse
    {
        $response = new ComparingComparingResponse();

        $newIndicators = [];

        //left
        $idLeft = $comparingRequest->idLeft;
        $monthLeft = $comparingRequest->monthLeft;

        $indicator_left = $this->indicatorRepository->find__with__targets_realizations__by__id($idLeft);

        $res = $indicator_left->targets->search(function ($value) use ($monthLeft) {
            return $value->month === $monthLeft;
        });
        $target_left = $res === false ? null : $indicator_left->targets[$res]->value;

        $res = $indicator_left->realizations->search(function ($value) use ($monthLeft) {
            return $value->month === $monthLeft;
        });
        $realization_left = $res === false ? null : $indicator_left->realizations[$res]->value;

        $newIndicators['left'] = $this->calc($indicator_left, $monthLeft, $target_left, $realization_left);

        //right
        $idRight = $comparingRequest->idRight;
        $monthRight = $comparingRequest->monthRight;

        $indicator_right = $this->indicatorRepository->find__with__targets_realizations__by__id($idRight);

        $res = $indicator_right->targets->search(function ($value) use ($monthRight) {
            return $value->month === $monthRight;
        });
        $target_right = $res === false ? null : $indicator_right->targets[$res]->value;

        $res = $indicator_right->realizations->search(function ($value) use ($monthRight) {
            return $value->month === $monthRight;
        });
        $realization_right = $res === false ? null : $indicator_right->realizations[$res]->value;

        $newIndicators['right'] = $this->calc($indicator_right, $monthRight, $target_right, $realization_right);

        $response->indicators = $newIndicators;

        return $response;
    }

    private function calc($indicator, $month, $target, $realization): array
    {
        $result = [];

        //perhitungan pencapaian
        $achievement = '-';
        if (!$indicator->dummy && !$indicator->reducing_factor && array_key_exists($month, $indicator->validity)) {
            if ($target == (float) 0 && $realization == (float) 0) {
                $achievement = 100;
            } else if ($target == (float) 0 && $realization !== (float) 0) {
                $achievement = 0;
            } else if ($indicator->getRawOriginal('polarity') === '1') {
                $achievement = $realization == (float) 0 ? 0 : ($realization / $target) * 100;
            } else if ($indicator->getRawOriginal('polarity') === '-1') {
                $achievement = $realization == (float) 0 ? 0 : (2 - ($realization / $target)) * 100;
            }
        }

        //perhitungan nilai capping 100%
        $capping_value_100 = '-';
        if (!$indicator->dummy && !$indicator->reducing_factor && array_key_exists($month, $indicator->validity)) {
            if ($target == (float) 0) {
                $capping_value_100 = 'BELUM DINILAI';
            } else if ($achievement <= (float) 0) {
                $capping_value_100 = 0;
            } else if ($achievement > (float) 0 && $achievement <= (float) 100) {
                $res = $achievement * $indicator->weight[$month];
                $capping_value_100 = $res == (float) 0 ? 0 : $res / 100;
            } else {
                $capping_value_100 = $indicator->weight[$month];
            }
        }

        //perhitungan nilai capping 110%
        $capping_value_110 = '-';
        if (!$indicator->dummy && !$indicator->reducing_factor && array_key_exists($month, $indicator->validity)) {
            if ($target == (float) 0) {
                $capping_value_110 = 'BELUM DINILAI';
            } else if ($achievement <= (float) 0) {
                $capping_value_110 = 0;
            } else if ($achievement > (float) 0 && $achievement <= (float) 110) {
                $res = $achievement * $indicator->weight[$month];
                $capping_value_110 = $res == (float) 0 ? 0 : $res / 100;
            } else {
                $res = $indicator->weight[$month] * 110;
                $capping_value_110 = $res == (float) 0 ? 0 : $res / 100;
            }
        }

        //perhitungan status & warna status
        $status = '-';
        $status_color = 'none';
        if (!$indicator->dummy && !$indicator->reducing_factor && array_key_exists($month, $indicator->validity)) {
            if ($target == (float) 0) {
                $status = 'BELUM DINILAI';
                $status_color = 'info';
            } else if ($achievement >= (float) 100) {
                $status = 'BAIK';
                $status_color = 'success';
            } else if ($achievement >= (float) 95 && $achievement < (float) 100) {
                $status = 'HATI-HATI';
                $status_color = 'warning';
            } else if ($achievement < (float) 95) {
                $status = 'MASALAH';
                $status_color = 'danger';
            }
        }

        $result['indicator'] = $indicator->indicator;
        $result['type'] = $indicator->type;
        $result['measure'] = is_null($indicator->measure) ? '-' : $indicator->measure;
        $result['polarity'] = $indicator->polarity;

        $result['achievement']['value']['original'] = $achievement;
        $result['achievement']['value']['showed'] = in_array(gettype($achievement), ['double', 'integer']) ? number_format($achievement, $this->decimals_showed, ',', '.') : $achievement;
        $result['status'] = $status;
        $result['status_color'] = $status_color;
        $result['capping_value_100']['value']['original'] = $capping_value_100;
        $result['capping_value_100']['value']['showed'] = in_array(gettype($capping_value_100), ['double', 'integer']) ? number_format($capping_value_100, $this->decimals_showed, ',', '.') : $capping_value_100;
        $result['capping_value_110']['value']['original'] = $capping_value_110;
        $result['capping_value_110']['value']['showed'] = in_array(gettype($capping_value_110), ['double', 'integer']) ? number_format($capping_value_110, $this->decimals_showed, ',', '.') : $capping_value_110;

        $selected_weight = '-';
        if (!$indicator->dummy && count($indicator->weight) !== 0 && array_key_exists($month, $indicator->validity)) {
            $selected_weight = (float) $indicator->weight[$month];
        }
        $result['selected_weight'] = $selected_weight;

        $selected_target = '-';
        if (!$indicator->dummy && !$indicator->reducing_factor && array_key_exists($month, $indicator->validity)) {
            $selected_target = (float) $target;
        }
        $result['selected_target']['value']['original'] = $selected_target;
        $result['selected_target']['value']['showed'] = in_array(gettype($selected_target), ['double', 'integer']) ? number_format($selected_target, $this->decimals_showed, ',', '.') : $selected_target;

        $selected_realization = '-';
        if (!$indicator->dummy && array_key_exists($month, $indicator->validity)) {
            $selected_realization = (float) $realization;
        }
        $result['selected_realization']['value']['original'] = $selected_realization;
        $result['selected_realization']['value']['showed'] = in_array(gettype($selected_realization), ['double', 'integer']) ? number_format($selected_realization, $this->decimals_showed, ',', '.') : $selected_realization;

        return $result;
    }
}
