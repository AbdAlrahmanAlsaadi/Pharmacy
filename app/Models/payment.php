<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'stripe_payment_id',
        'stripe_session_id',
        'amount',
        'currency',
        'status'
    ];
}
