<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Telescope\Telescope;

class LogOrderCreation
{
    public function handle(OrderCreated $event)
    {
        // الحصول على المستخدم الحالي (إذا كان مسجل دخول)
        $user = Auth::user() ?? new User(['name' => 'System']);

        // تسجيل العملية في Telescope
        Telescope::recordOrderOperation(
            operation: 'created',
            order: $event->order,
            user: $user
        );
    }
}
