<?php

namespace App\Services;

class TaxCalculator
{
    /**
     * @var float PPN Rate (e.g. 11 for 11%)
     */
    protected float $taxRate;

    public function __construct(float $taxRate = 11.0)
    {
        $this->taxRate = $taxRate;
    }

    /**
     * Calculate tax details for a given base amount (DPP)
     *
     * @param float $amount The base amount (DPP)
     * @return array
     */
    public function calculate(float $amount): array
    {
        $taxAmount = $amount * ($this->taxRate / 100);
        $total = $amount + $taxAmount;

        return [
            'dpp_amount' => round($amount, 2),
            'tax_percentage' => $this->taxRate,
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($total, 2)
        ];
    }
}
