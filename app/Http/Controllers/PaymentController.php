<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{

    // ===========================
    // 💳 إنشاء دفع للطلب
    // ===========================
    public function create(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $order = Order::findOrFail($request->order_id);
        $user = Auth::user();

        // تحقق ملكية الطلب
        if ($order->pharmacist_id !== $user->id) {
            return response()->json([
                'error' => 'Unauthorized order'
            ], 403);
        }

        // تحقق إذا مدفوع مسبقاً
        if (Payment::where('order_id', $order->id)
            ->where('status', 'succeeded')
            ->exists()
        ) {
            return response()->json([
                'error' => 'Order already paid'
            ], 400);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {

            $paymentIntent = PaymentIntent::create([
                'amount' => $order->total_price * 100,
                'currency' => 'usd',
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'type' => 'order'
                ],
                'receipt_email' => $user->email,
                'automatic_payment_methods' => [
                    'enabled' => true
                ]
            ]);

            Payment::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'stripe_payment_id' => $paymentIntent->id,
                'amount' => $order->total_price,
                'currency' => 'usd',
                'status' => 'pending'
            ]);

            return response()->json([
                'client_secret' => $paymentIntent->client_secret
            ]);
        } catch (\Exception $e) {

            Log::error('Stripe error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Payment creation failed'
            ], 500);
        }
    }


    // ===========================
    // 🔔 Webhook (أهم شي)
    // ===========================
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                env('STRIPE_WEBHOOK_SECRET')
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid webhook'], 400);
        }

        switch ($event->type) {

            case 'payment_intent.succeeded':

                $intent = $event->data->object;

                // 🟢 حالة طلب
                if ($intent->metadata->type === 'order') {

                    $payment = Payment::where('stripe_payment_id', $intent->id)->first();

                    if ($payment && $payment->status !== 'succeeded') {

                        $payment->update(['status' => 'succeeded']);

                        Order::where('id', $intent->metadata->order_id)
                            ->update(['status' => 'paid']);
                    }
                }

                // 🟢 شحن محفظة
                if ($intent->metadata->type === 'wallet') {

                    $user = \App\Models\User::find($intent->metadata->user_id);

                    $user->increment('balance', $intent->amount / 100);
                }

                break;


            case 'payment_intent.payment_failed':

                $intent = $event->data->object;

                Payment::where('stripe_payment_id', $intent->id)
                    ->update(['status' => 'failed']);

                break;
        }

        return response()->json(['status' => 'success']);
    }


    // ===========================
    // 💰 شحن محفظة
    // ===========================
    public function topUp(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $user = Auth::user();

        Stripe::setApiKey(env('STRIPE_SECRET'));

        $intent = PaymentIntent::create([
            'amount' => $request->amount * 100,
            'currency' => 'usd',
            'metadata' => [
                'type' => 'wallet',
                'user_id' => $user->id
            ]
        ]);

        return response()->json([
            'client_secret' => $intent->client_secret
        ]);
    }


    // ===========================
    // 🔍 تحقق حالة الدفع
    // ===========================
    public function checkStatus(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required'
        ]);

        $payment = Payment::where('stripe_payment_id', $request->payment_intent_id)->first();

        if (!$payment) {
            return response()->json([
                'error' => 'Payment not found'
            ], 404);
        }

        return response()->json([
            'status' => $payment->status,
            'order_id' => $payment->order_id
        ]);
    }



public function success(Request $request)
    {

        $request->validate([
            'payment_intent_id' => 'required'
        ]);

        $payment = Payment::where('stripe_payment_id', $request->payment_intent_id)->first();

        if (!$payment) {

            return response()->json([
                'error' => 'Payment not found'
            ], 404);
        }

        $payment->update([
            'status' => 'succeeded'
        ]);

        Order::where('id', $payment->order_id)
            ->update(['status' => 'paid']);

        return response()->json([
            'message' => 'Payment successful'
        ]);



    }
    public function cancel(Request $request)
    {

        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $payment = Payment::where('order_id', $request->order_id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($payment) {

            $payment->update([
                'status' => 'canceled'
            ]);
        }

        return response()->json([
            'message' => 'Payment canceled'
        ]);

        }
    }
