<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class RedisCacheService
{
    /**
     * Check if Redis server is reachable
     */
    public static function isRedisAvailable(): bool
    {
        try {
            if (config('database.redis.client') === 'predis' || class_exists('\Predis\Client')) {
                Redis::ping();
                return true;
            }
        } catch (\Throwable $e) {
            // Redis offline
        }
        return false;
    }

    /**
     * Get or cache branch products for fast POS barcode lookup (TTL: 1 hour)
     */
    public static function getBranchProducts(int $branchId)
    {
        $cacheKey = "branch_products_pos_{$branchId}";

        try {
            return Cache::remember($cacheKey, 3600, function () use ($branchId) {
                return DB::table('product_branches')
                    ->join('products', 'product_branches.product_id', '=', 'products.id')
                    ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                    ->where('product_branches.branch_id', $branchId)
                    ->select(
                        'products.id',
                        'products.sku',
                        'products.barcode',
                        'products.name',
                        'product_branches.price as selling_price',
                        'product_branches.cost_price',
                        'products.unit',
                        'categories.name as category_name',
                        'product_branches.stock'
                    )
                    ->get();
            });
        } catch (\Throwable $e) {
            // Fallback directly to DB query if cache fails
            return DB::table('product_branches')
                ->join('products', 'product_branches.product_id', '=', 'products.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->where('product_branches.branch_id', $branchId)
                ->select(
                    'products.id',
                    'products.sku',
                    'products.barcode',
                    'products.name',
                    'product_branches.price as selling_price',
                    'product_branches.cost_price',
                    'products.unit',
                    'categories.name as category_name',
                    'product_branches.stock'
                )
                ->get();
        }
    }

    /**
     * Invalidate branch products cache when stock or price is modified
     */
    public static function invalidateBranchProducts(int $branchId): void
    {
        try {
            Cache::forget("branch_products_pos_{$branchId}");
        } catch (\Throwable $e) {
            // Ignore cache error
        }
    }

    /**
     * Cache active blocked IPs list for instant WAF checking
     */
    public static function getCachedBlockedIps(): array
    {
        try {
            return Cache::remember('firewall_blocked_ips', 300, function () {
                return DB::table('blocked_ips')->pluck('ip_address')->toArray();
            });
        } catch (\Throwable $e) {
            return DB::table('blocked_ips')->pluck('ip_address')->toArray();
        }
    }

    /**
     * Invalidate blocked IPs cache
     */
    public static function invalidateBlockedIps(): void
    {
        try {
            Cache::forget('firewall_blocked_ips');
        } catch (\Throwable $e) {
            // Ignore cache error
        }
    }
}
