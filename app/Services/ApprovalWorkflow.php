<?php

namespace App\Services;

use App\Models\Transaction;

class ApprovalWorkflow
{
    /**
     * Determine the required approvals based on the transaction's risk score.
     *
     * @param Transaction $transaction
     * @return array Array of required roles
     */
    public function determineRequiredApprovals(Transaction $transaction): array
    {
        $score = $transaction->risk_score;
        $approvals = [];

        // Low risk: Kasir / Admin Cabang can proceed directly. No special approval needed.
        if ($score < 20) {
            return $approvals; // Empty means auto-approved or pre-approved
        }

        // Medium risk: Needs Admin Cabang approval
        if ($score >= 20 && $score < 75) {
            $approvals[] = [
                'level' => 1,
                'role_required' => 'Admin Cabang'
            ];
        }

        // High risk: Needs Admin Cabang AND Admin Pusat approval
        if ($score >= 75) {
            $approvals[] = [
                'level' => 1,
                'role_required' => 'Admin Cabang'
            ];
            $approvals[] = [
                'level' => 2,
                'role_required' => 'Admin Pusat'
            ];
        }

        return $approvals;
    }

    /**
     * Apply the required approvals to the transaction
     */
    public function processApprovals(Transaction $transaction, array $approvals)
    {
        if (empty($approvals)) {
            $transaction->status = 'approved';
            $transaction->save();
            return;
        }

        $transaction->status = 'pending';
        $transaction->save();

        foreach ($approvals as $approval) {
            $transaction->approvals()->create([
                'role_required' => $approval['role_required'],
                'level' => $approval['level'],
                'status' => 'pending'
            ]);
        }
    }
}
