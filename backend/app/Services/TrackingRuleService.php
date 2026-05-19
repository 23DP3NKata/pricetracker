<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TrackingRuleService
{
    public function getRuleStatsByProductId(int $userId): array
    {
        $ruleStats = DB::table('user_products as up')
            ->join('products as p', 'p.id', '=', 'up.product_id')
            ->where('up.user_id', $userId)
            ->where('p.status', 'active')
            ->groupBy('up.product_id')
            ->select([
                'up.product_id',
                DB::raw('COUNT(*) as rules_count'),
                DB::raw('SUM(CASE WHEN up.is_active = 1 AND up.last_notified_at IS NULL THEN 1 ELSE 0 END) as active_count'),
                DB::raw('SUM(CASE WHEN up.last_notified_at IS NOT NULL THEN 1 ELSE 0 END) as completed_count'),
            ])
            ->get();

        $ruleStatsByProductId = [];
        foreach ($ruleStats as $statRow) {
            $ruleStatsByProductId[$statRow->product_id] = [
                'rules_count' => (int) $statRow->rules_count,
                'active_count' => (int) $statRow->active_count,
                'completed_count' => (int) $statRow->completed_count,
            ];
        }

        return $ruleStatsByProductId;
    }
}
