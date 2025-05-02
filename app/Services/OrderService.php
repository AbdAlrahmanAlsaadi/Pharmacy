<?php
namespace App\Services;

use App\Models\Medication;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Medicine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\NewAccessToken;
use TCPDF;

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

    public function generateOrdersReport($startDate, $endDate, $download = false)
    {
        // التحقق من صحة التواريخ
        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            throw new \Exception("تاريخ البداية يجب أن يكون قبل تاريخ النهاية");
        }

        // جلب البيانات مع التحقق من العلاقات
        $orders = Order::with(['pharmacist', 'items.medicine'])
            ->whereDate('created_at', '>=', Carbon::parse($startDate)->startOfDay())
            ->whereDate('created_at', '<=', Carbon::parse($endDate)->endOfDay())
            ->get();

        // تسجيل البيانات للتحقق
        Log::info('Orders Data:', [
            'count' => $orders->count(),
            'sample' => $orders->first() ? $orders->first()->toArray() : null,
            'period' => [$startDate, $endDate]
        ]);
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // إعداد المستند
        $pdf->SetCreator('Pharmacy System');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('تقرير الطلبات');
        $pdf->SetSubject('تقرير الطلبات');
        $pdf->setRTL(true); // للنص العربي

        // إزالة الهيدر والفوتر الافتراضيين
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // إضافة صفحة جديدة
        $pdf->AddPage();

        // محتوى التقرير
        $this->generateReportContent($pdf, $orders, $startDate, $endDate);

        // اسم الملف مع التاريخ ليكون فريداً
        $fileName = 'orders_report_'.date('Ymd_His').'.pdf';

        // تحديد خيار الإخراج بناء على طلب التحميل
        if ($download) {
            // تحميل مباشر مع إنهاء التنفيذ
            $pdf->Output($fileName, 'D');
            exit;
        }

        // إرجاع المحتوى كسلسلة للاستخدام البرمجي
        return [
            'content' => $pdf->Output($fileName, 'S'),
            'file_name' => $fileName
        ];
    }
    protected function generateReportContent($pdf, $orders, $startDate, $endDate)
    {
        // التحقق من وجود بيانات
        if ($orders->isEmpty()) {
            $pdf->SetFont('aealarabiya', 'B', 14);
            $pdf->Cell(0, 10, 'لا توجد بيانات متاحة للفترة المحددة', 0, 1, 'C');
            return;
        }

        // العنوان الرئيسي (يظهر فقط إذا كانت هناك بيانات)
        $pdf->SetFont('aealarabiya', 'B', 18);
        $pdf->Cell(0, 10, 'تقرير الطلبات', 0, 1, 'C');
        $pdf->Ln(10);

        // معلومات الفترة
        $pdf->SetFont('aealarabiya', '', 12);
        $pdf->Cell(0, 10, 'الفترة: ' . $this->formatDate($startDate) . ' إلى ' . $this->formatDate($endDate), 0, 1, 'R');
        $pdf->Ln(15);

        // جدول الطلبات
        $this->generateOrdersTable($pdf, $orders);

        // الإجماليات
        $pdf->SetFont('aealarabiya', 'B', 14);
        $pdf->Cell(0, 10, 'إجمالي الطلبات: ' . $orders->count(), 0, 1, 'R');
        $pdf->Cell(0, 10, 'إجمالي المبيعات: ' . number_format($orders->sum('total_price'), 2) . ' ر.س', 0, 1, 'R');
    }

    protected function generateOrdersTable($pdf, $orders)
    {
        $pdf->SetFont('aealarabiya', 'B', 12);

        // رأس الجدول
        $headers = ['رقم الطلب', 'الصيدلاني', 'التاريخ', 'الحالة', 'المبلغ'];
        $widths = [25, 60, 40, 40, 40];

        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C');
        }
        $pdf->Ln();

        // محتوى الجدول
        $pdf->SetFont('aealarabiya', '', 10);
        foreach ($orders as $order) {
            // التحقق من وجود العلاقات
            $pharmacistName = $order->pharmacist ? $order->pharmacist->name : 'غير معروف';

            $pdf->Cell($widths[0], 6, $order->id, 'LR');
            $pdf->Cell($widths[1], 6, $pharmacistName, 'LR');
            $pdf->Cell($widths[2], 6, $order->created_at->format('Y-m-d'), 'LR', 0, 'C');
            $pdf->Cell($widths[3], 6, $order->status, 'LR', 0, 'C');
            $pdf->Cell($widths[4], 6, number_format($order->total_price, 2), 'LR', 0, 'R');
            $pdf->Ln();
        }

        $pdf->Cell(array_sum($widths), 0, '', 'T');
    }


    protected function formatDate($date)
    {
        return Carbon::parse($date)->format('Y-m-d');
    }
}
