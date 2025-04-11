<?php

namespace App\Http\Controllers;

use App\Events\AdminNotificationEvent;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Container\Attributes\Auth as AttributesAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{

    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index()
    {
        $user = Auth::user();

        if ($user->type === 'pharmacist') {
            $orders = $this->orderService->getPharmacistOrders($user->id);
        } else {
            $orders = $this->orderService->getAllOrders();
        }

        return response()->json($orders);
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->createOrder(
            $request->validated(),
            Auth::id()

        );

        return response()->json($order, 201);
    }
    public function updateStatus(UpdateOrderStatusRequest $request, $orderId)
    {
        $order = Order::with('items.medicine')->findOrFail($orderId);

        Log::debug('Updating order status', [
            'order_id' => $order->id,
            'current_status' => $order->status,
            'new_data' => $request->validated()
        ]);

        try {
            $updatedOrder = $this->orderService->updateOrderStatus($order, $request->validated());
            event(new AdminNotificationEvent('order status updated ', $updatedOrder));

            return response()->json([
                'success' => true,
                'order' => $updatedOrder,
                'message' => 'Order status updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Order status update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
