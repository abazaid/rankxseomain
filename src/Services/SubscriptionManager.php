<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\StoreRepository;
use App\Repositories\SaaSRepository;
use App\Support\Database;
use App\Support\Plans;

final class SubscriptionManager
{
    private const MODE_COSTS = [
        'domain_seo' => 3,
    ];

    private function getModeCost(string $mode): int
    {
        $cost = self::MODE_COSTS[$mode] ?? 1;
        return max(1, (int) $cost);
    }

    public function refreshPeriodIfNeeded(array $store): array
    {
        $subscription = $store['subscription'] ?? [];
        $merchantId = (string) ($store['merchant_id'] ?? '');

        if ($merchantId === '' || empty($subscription['period_ends_at'])) {
            return $store;
        }

        $endsAt = strtotime((string) $subscription['period_ends_at']);
        $status = (string) ($subscription['status'] ?? 'inactive');

        if ($endsAt !== false && $endsAt < time() && in_array($status, ['active', 'trial'], true)) {
            $plan = Plans::get((string) ($subscription['plan_name'] ?? Plans::BUDGET_TRIAL));
            $totalQuota = $plan !== null ? array_sum($plan['quotas']) : 0;

            $subscription['used_products'] = 0;
            if ($plan !== null) {
                foreach ($plan['quotas'] as $key => $value) {
                    $subscription['quota_' . $key] = (int) $value;
                    $subscription['used_' . $key] = 0;
                }
            }
            $subscription['product_quota'] = $totalQuota;
            $subscription['period_started_at'] = date('c');
            $intervalDays = max((int) ($subscription['interval_days'] ?? 30), 1);
            $subscription['period_ends_at'] = date('c', strtotime('+' . $intervalDays . ' days'));
            $subscription['last_event'] = 'period.rolled';

            $store['subscription'] = $subscription;
            (new StoreRepository())->save($merchantId, ['subscription' => $subscription]);
        }

        return $store;
    }

    public function canOptimize(array $store, string $type = 'product_description', int $cost = 1): bool
    {
        $subscription = $store['subscription'] ?? [];
        $status = (string) ($subscription['status'] ?? 'inactive');

        if (!in_array($status, ['active', 'trial'], true)) {
            return false;
        }

        $usedKey = 'used_' . $type;
        $quotaKey = 'quota_' . $type;
        
        $used = (int) ($subscription[$usedKey] ?? 0);
        $quota = (int) ($subscription[$quotaKey] ?? 0);
        $cost = max(1, $cost);

        if ($quota <= 0) {
            $plan = Plans::get((string) ($subscription['plan_name'] ?? Plans::BUDGET_TRIAL));
            if ($plan !== null) {
                $quota = (int) ($plan['quotas'][$type] ?? 0);
            }
        }

        return ($used + $cost) <= $quota;
    }

    public function canSpendPoints(array $store, int $cost = 1): bool
    {
        $subscription = $store['subscription'] ?? [];
        $status = (string) ($subscription['status'] ?? 'inactive');
        if (!in_array($status, ['active', 'trial'], true)) {
            return false;
        }

        $cost = max(1, $cost);
        $quota = (int) ($subscription['product_quota'] ?? 0);
        $used = (int) ($subscription['used_products'] ?? 0);

        if ($quota <= 0) {
            $plan = Plans::get((string) ($subscription['plan_name'] ?? Plans::BUDGET_TRIAL));
            if ($plan !== null) {
                $quota = (int) array_sum((array) ($plan['quotas'] ?? []));
            }
        }

        return ($used + $cost) <= $quota;
    }

    public function recordOptimization(array $store, int $productId, ?string $productName, string $mode = 'all', string $status = 'completed', ?int $cost = null): array
    {
        $merchantId = (string) ($store['merchant_id'] ?? '');
        $subscription = $store['subscription'] ?? [];
        $points = $cost !== null ? max(1, $cost) : $this->getModeCost($mode);

        $modeToQuota = [
            'description' => ['product_description'],
            'seo' => ['product_seo'],
            'all' => ['product_description', 'product_seo'],
            'image_alt' => ['image_alt'],
            'image_alt_bulk' => ['image_alt'],
            'store_seo' => ['product_seo'],
            'keyword_research' => ['keyword_research'],
            'domain_seo' => ['domain_seo'],
            'brand_seo' => ['brand_seo'],
            'category_seo' => ['category_seo'],
        ];

        $quotaTypes = $modeToQuota[$mode] ?? ['product_description'];
        foreach ($quotaTypes as $quotaType) {
            $usedKey = 'used_' . $quotaType;
            $subscription[$usedKey] = (int) ($subscription[$usedKey] ?? 0) + $points;
        }
        
        $subscription['used_products'] = (int) ($subscription['used_products'] ?? 0) + $points;
        $store['subscription'] = $subscription;

        $logs = $store['usage_logs'] ?? [];
        $logs[] = [
            'product_id' => $productId,
            'product_name' => $productName,
            'mode' => $mode,
            'status' => $status,
            'points' => $points,
            'used_at' => date('c'),
        ];
        $store['usage_logs'] = array_slice($logs, -200);

        if ($merchantId !== '') {
            (new StoreRepository())->save($merchantId, [
                'subscription' => $subscription,
                'usage_logs' => $store['usage_logs'],
            ]);

            if (Database::isAvailable()) {
                (new SaaSRepository())->incrementUsageByMerchantId((int) $merchantId, $points);
            }
        }

        return $store;
    }

    public function activateSubscription(array $store, string $planName, string $event, int $intervalDays = 30, string $validTill = ''): array
    {
        $plan = Plans::get(Plans::mapPlanAlias($planName));
        $planId = $plan !== null ? $plan['id'] : Plans::STARTER;
        
        if ($plan === null) {
            $plan = Plans::get(Plans::STARTER);
        }

        $subscription = $store['subscription'] ?? [];
        $subscription['status'] = 'active';
        $subscription['plan_name'] = $planId;
        $subscription['plan_name_original'] = $planName;
        
        foreach ($plan['quotas'] as $key => $value) {
            $subscription['quota_' . $key] = $value;
            $subscription['used_' . $key] = 0;
        }
        
        $subscription['product_quota'] = array_sum($plan['quotas']);
        $subscription['used_products'] = 0;
        $subscription['period_started_at'] = date('c');
        
        if ($validTill !== '') {
            $subscription['period_ends_at'] = $validTill;
        } else {
            $subscription['period_ends_at'] = date('c', strtotime('+' . $intervalDays . ' days'));
        }
        
        $subscription['interval_days'] = $intervalDays;
        $subscription['last_event'] = $event;

        if (!empty($store['merchant_id']) && Database::isAvailable()) {
            (new SaaSRepository())->syncSubscriptionByMerchantId((int) $store['merchant_id'], $subscription);
        }

        return $subscription;
    }

    public function deactivateSubscription(array $store, string $event): array
    {
        $subscription = $store['subscription'] ?? [];
        $subscription['status'] = 'inactive';
        $subscription['last_event'] = $event;

        if (!empty($store['merchant_id']) && Database::isAvailable()) {
            (new SaaSRepository())->syncSubscriptionByMerchantId((int) $store['merchant_id'], $subscription);
        }

        return $subscription;
    }

    public function startTrial(array $store): array
    {
        $plan = Plans::get(Plans::BUDGET_TRIAL);
        
        $subscription = $store['subscription'] ?? [];
        $subscription['status'] = 'trial';
        $subscription['plan_name'] = Plans::BUDGET_TRIAL;
        
        foreach ($plan['quotas'] as $key => $value) {
            $subscription['quota_' . $key] = $value;
            $subscription['used_' . $key] = 0;
        }
        
        $subscription['product_quota'] = array_sum($plan['quotas']);
        $subscription['used_products'] = 0;
        $subscription['period_started_at'] = date('c');
        $subscription['period_ends_at'] = date('c', strtotime('+30 days'));
        $subscription['last_event'] = 'trial.started';

        return $subscription;
    }

    public function summary(array $store): array
    {
        $subscription = $store['subscription'] ?? [];
        $planId = (string) ($subscription['plan_name'] ?? Plans::BUDGET_TRIAL);
        $plan = Plans::get($planId);

        $quotas = [];
        $used = [];
        
        if ($plan !== null) {
            foreach ($plan['quotas'] as $key => $quota) {
                $usedKey = 'used_' . $key;
                $used[$key] = (int) ($subscription[$usedKey] ?? 0);
                $quotas[$key] = [
                    'quota' => $quota,
                    'used' => $used[$key],
                    'remaining' => max($quota - $used[$key], 0),
                ];
            }
        }

        return [
            'status' => $subscription['status'] ?? 'inactive',
            'plan_id' => $planId,
            'plan_name' => $plan !== null ? $plan['name_ar'] : 'غير محدد',
            'plan' => $plan,
            'quotas' => $quotas,
            'product_quota' => (int) ($subscription['product_quota'] ?? 0),
            'used_products' => (int) ($subscription['used_products'] ?? 0),
            'remaining_products' => max((int) ($subscription['product_quota'] ?? 0) - (int) ($subscription['used_products'] ?? 0), 0),
            'period_started_at' => $subscription['period_started_at'] ?? null,
            'period_ends_at' => $subscription['period_ends_at'] ?? null,
            'last_event' => $subscription['last_event'] ?? null,
        ];
    }
}
