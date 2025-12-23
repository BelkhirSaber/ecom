<?php

namespace App\Services\Inventory;

use App\Exceptions\InsufficientStockException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    public function __construct(private DatabaseManager $db)
    {
    }

    /**
     * Synchronise the stock quantity for a stockable model and record the movement.
     */
    public function syncStock(
        Model $stockable,
        int $newQuantity,
        string $reason,
        array $metadata = [],
        ?int $userId = null,
        ?string $overrideStatus = null,
        ?string $description = null
    ): ?\App\Models\StockMovement {
        $newQuantity = (int) $newQuantity;

        if ($newQuantity < 0) {
            throw new InsufficientStockException('Stock quantity cannot be negative.');
        }

        $status = $overrideStatus ?? ($newQuantity > 0 ? 'in_stock' : 'out_of_stock');

        if (! in_array($status, ['in_stock', 'out_of_stock', 'preorder'], true)) {
            throw new InsufficientStockException('Invalid stock status provided.');
        }

        return $this->db->transaction(function () use ($stockable, $newQuantity, $reason, $metadata, $userId, $status, $description) {
            $locked = $stockable->newQuery()
                ->whereKey($stockable->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $currentQuantity = (int) ($locked->stock_quantity ?? 0);
            $currentStatus = (string) ($locked->stock_status ?? 'out_of_stock');
            $difference = $newQuantity - $currentQuantity;

            if ($difference === 0 && $status === $currentStatus) {
                return null;
            }

            $locked->forceFill([
                'stock_quantity' => $newQuantity,
                'stock_status' => $status,
            ])->save();

            $movement = $locked->stockMovements()->create([
                'user_id' => $userId,
                'quantity' => $difference,
                'balance_after' => $newQuantity,
                'reason' => $reason,
                'description' => $description,
                'metadata' => $metadata,
            ]);

            Log::channel('catalogue')->info('stock.movement.recorded', [
                'stockable_type' => $locked::class,
                'stockable_id' => $locked->getKey(),
                'movement_id' => $movement->id,
                'difference' => $difference,
                'balance_after' => $newQuantity,
                'reason' => $reason,
                'status_before' => $currentStatus,
                'status_after' => $status,
            ]);

            return $movement;
        });
    }

    public function decrementStock(
        Model $stockable,
        int $quantity,
        string $reason,
        array $metadata = [],
        ?int $userId = null,
        ?string $description = null
    ): \App\Models\StockMovement {
        $quantity = (int) $quantity;

        if ($quantity <= 0) {
            throw new InsufficientStockException('Decrement quantity must be greater than zero.');
        }

        return $this->db->transaction(function () use ($stockable, $quantity, $reason, $metadata, $userId, $description) {
            $locked = $stockable->newQuery()
                ->whereKey($stockable->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $currentQuantity = (int) ($locked->stock_quantity ?? 0);
            $currentStatus = (string) ($locked->stock_status ?? 'out_of_stock');
            $newQuantity = $currentQuantity - $quantity;

            if ($newQuantity < 0) {
                throw new InsufficientStockException('Insufficient stock available for this operation.');
            }

            $newStatus = $newQuantity > 0 ? 'in_stock' : 'out_of_stock';

            $locked->forceFill([
                'stock_quantity' => $newQuantity,
                'stock_status' => $newStatus,
            ])->save();

            $movement = $locked->stockMovements()->create([
                'user_id' => $userId,
                'quantity' => -$quantity,
                'balance_after' => $newQuantity,
                'reason' => $reason,
                'description' => $description,
                'metadata' => $metadata,
            ]);

            Log::channel('catalogue')->info('stock.movement.recorded', [
                'stockable_type' => $locked::class,
                'stockable_id' => $locked->getKey(),
                'movement_id' => $movement->id,
                'difference' => -$quantity,
                'balance_after' => $newQuantity,
                'reason' => $reason,
                'status_before' => $currentStatus,
                'status_after' => $newStatus,
            ]);

            return $movement;
        });
    }
}
