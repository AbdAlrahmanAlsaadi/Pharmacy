<?php

namespace App\Http\Controllers;

use App\Events\AdminNotificationEvent;
use App\Exports\OrdersExport;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Container\Attributes\Auth as AttributesAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

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
    // app/Http/Controllers/ReportController.php


    // لتحميل الملف مباشرة
    public function downloadReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        // هذا سيسبب تحميل الملف مباشرة في المتصفح
        $this->orderService->generateOrdersReport(
            $request->start_date,
            $request->end_date,
            true // تفعيل خيار التحميل المباشر
        );
    }

    // للحصول على ملف PDF كرد API (بدون تحميل مباشر)
    public function getReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $result = $this->orderService->generateOrdersReport(
            $request->start_date,
            $request->end_date
        );

        return response()->make($result['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$result['file_name'].'"'
        ]);
    }

    public function exportOrders(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        return Excel::download(
            new OrdersExport($request->start_date, $request->end_date),
            'orders_report.xlsx'
        );
    }
}
