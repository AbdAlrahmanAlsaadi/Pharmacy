<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{

    public function testBalance()
    {
        /** @var \App\Models\User $user */

        $user = Auth::user();
        $user->balance += 1000;
        $user->save();

        return "balance updated";
    }

    public function create(Request $request)
{
    $request->validate([
        'order_id' => 'required|exists:orders,id',
        'payment_method' => 'required|in:stripe,wallet'
    ]);
    $order = Order::findOrFail($request->order_id);
        /** @var \App\Models\User $user */
    $user = Auth::user();

    // التحقق من صاحب الطلب
    if ($order->pharmacist_id !== $user->id) {
        return response()->json(['error' => 'Unauthorized order'], 403);
    }

    // التحقق إذا مدفوع مسبقاً
    if ($order->payment_status === 'paid') {
        return response()->json(['error' => 'Order already paid'], 400);
    }

    /*
    ====================================
    🟢 الدفع من المحفظة
    ====================================
    */
    if ($request->payment_method === 'wallet') {

        if ($user->balance < $order->total_price) {
            return response()->json([
                'error' => 'Insufficient balance'
            ], 400);
        }

        // خصم الرصيد
        $user->balance -= $order->total_price;
        $user->save();

        // تحديث الطلب
        $order->update([
            'status' => 'paid',
            'payment_status' => 'paid'
        ]);

        // تسجيل العملية
        \App\Models\WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => $order->total_price,
            'type' => 'withdraw'
        ]);

        return response()->json([
            'message' => 'Paid successfully using wallet'
        ]);
    }

    /*
    ====================================
    🔵 الدفع عبر Stripe
    ====================================
    */
    Stripe::setApiKey(env('STRIPE_SECRET'));

    try {

        $paymentIntent = PaymentIntent::create([
            'amount' => $order->total_price * 100,
            'currency' => 'usd',
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'type' => 'order_payment'
            ],
            'receipt_email' => $user->email,
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
            'payment_intent_id' => $paymentIntent->id,
            'client_secret' => $paymentIntent->client_secret
        ]);

    } catch (\Exception $e) {

        Log::error('Stripe error: ' . $e->getMessage());

        return response()->json([
            'error' => 'Payment creation failed'
        ], 500);
    }
}



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

                /*
            ============================
            🟢 شحن محفظة
            ============================
            */
                if ($intent->metadata->type === 'wallet_topup') {

                    $user = \App\Models\User::find($intent->metadata->user_id);
                    $amount = $intent->amount / 100;

                    $user->balance += $amount;
                    $user->save();

                    \App\Models\WalletTransaction::create([
                        'user_id' => $user->id,
                        'amount' => $amount,
                        'type' => 'deposit'
                    ]);
                }

                /*
            ============================
            🔵 دفع طلب
            ============================
            */
                if ($intent->metadata->type === 'order_payment') {

                    $payment = Payment::where('stripe_payment_id', $intent->id)->first();

                    if ($payment && $payment->status !== 'succeeded') {

                        $payment->update(['status' => 'succeeded']);

                        Order::where('id', $intent->metadata->order_id)
                            ->update([
                                'status' => 'paid',
                                'payment_status' => 'paid'
                            ]);
                    }
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

    public function topUp(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $user = Auth::user();

        Stripe::setApiKey(env('STRIPE_SECRET'));

        $paymentIntent = PaymentIntent::create([
            'amount' => $request->amount * 100,
            'currency' => 'usd',
            'metadata' => [
                'type' => 'wallet_topup',
                'user_id' => $user->id
            ]
        ]);

        return response()->json([
            'client_secret' => $paymentIntent->client_secret
        ]);
    }
}
