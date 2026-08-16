<?php

namespace App\Services;

use App\Models\Transaction;

class RiskScoreCalculator
{
    /**
     * Calculate risk score for a transaction based on various parameters.
     * Returns a score from 0 to 100.
     *
     * @param float $totalAmount
     * @param int $itemsCount
     * @return int
     */
    public function calculate(float $totalAmount, int $itemsCount): int
    {
        $score = 0;

        // Factor 1: Transaction value
        if ($totalAmount > 50000000) { // > 50 Juta
            $score += 50;
        } elseif ($totalAmount > 10000000) { // > 10 Juta
            $score += 20;
        }

        // Factor 2: Unusual number of items
        if ($itemsCount > 100) {
            $score += 30;
        } elseif ($itemsCount > 50) {
            $score += 10;
        }

        // Additional AI or historical factors can be appended here

        return min($score, 100);
    }
}
