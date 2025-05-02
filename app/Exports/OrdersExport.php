<?php

namespace App\Exports;

use Maatwebsite\Excel\Files\NewExcelFile;
use App\Models\Order;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;

class OrdersExport

{
    public function export($startDate, $endDate)
    {
        $orders = Order::with(['pharmacist', 'items.medicine'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $data = [];

        // Headers
        $data[] = [
            'Order ID', 'Pharmacist', 'Date', 'Status', 'Total'
        ];

        // Data
        foreach ($orders as $order) {
            $data[] = [
                $order->id,
                $order->pharmacist->name,
                $order->created_at->format('Y-m-d'),
                $order->status,
                $order->total_price
            ];
        }

        Excel::create('orders_report', function($excel) use ($data) {
            $excel->sheet('Orders', function($sheet) use ($data) {
                $sheet->fromArray($data, null, 'A1', false, false);
            });
        })->download('xlsx');
    }
}
