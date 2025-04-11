<?php
namespace App\Services;

use App\Models\Medication;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Medicine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\NewAccessToken;

class OrderService
{
public function createOrder(array $data, int $pharmacistId): Order
{
return DB::transaction(function () use ($data, $pharmacistId) {
$order = Order::create([
'pharmacist_id' => $pharmacistId,
'status' => 'preparing',
'payment_status' => 'unpaid',
'total_price' => 0,
]);

$totalPrice = 0;

foreach ($data['items'] as $item) {
$medicine = Medication::findOrFail($item['medicine_id']);

$orderItem = new OrderItem([
'medicine_id' => $medicine->id,
'quantity' => $item['quantity'],
'price' => $medicine->price
]);

$order->items()->save($orderItem);
$totalPrice += $medicine->price * $item['quantity'];
}

$order->update(['total_price' => $totalPrice]);

return $order->load('items.medicine');
});
}
    public function updateOrderStatus(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $previousStatus = $order->status;
            $newStatus = $data['status'] ?? $previousStatus;

            $order->update($data);

            if ($newStatus === Order::STATUS_SENT && $previousStatus !== Order::STATUS_SENT) {
                $this->deductMedicinesFromStock($order);



            }

            return $order->load(['items.medicine', 'pharmacist']);
        });
    }

protected function deductMedicinesFromStock(Order $order): void
{
foreach ($order->items as $item) {
$medicine = $item->medicine;
$medicine->decrement('quantity', $item->quantity);

if ($medicine->quantity < 0) {
    throw new \Exception("Not enough stock for medicine: {$medicine->commercial_name}");
    }
    }
    }

    public function getPharmacistOrders(int $pharmacistId)
    {
    return Order::with(['items.medicine'])
    ->where('pharmacist_id', $pharmacistId)
    ->latest()
    ->get();
    }

    public function getAllOrders()
    {
    return Order::with(['pharmacist', 'items.medicine'])
    ->latest()
    ->get();
    }
    }
