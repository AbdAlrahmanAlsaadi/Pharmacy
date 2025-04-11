<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Order extends Model
{
// حالات الطلب
const STATUS_PREPARING = 'preparing';
const STATUS_SENT = 'sent';
const STATUS_RECEIVED = 'received';
const STATUS_CANCELLED = 'cancelled';

// حالات الدفع
const PAYMENT_UNPAID = 'unpaid';
const PAYMENT_PAID = 'paid';
const PAYMENT_PARTIAL = 'partial';

protected $fillable = [
'pharmacist_id',
'status',
'payment_status',
'total_price',

];

public function pharmacist(): BelongsTo
{
return $this->belongsTo(User::class, 'pharmacist_id');
}


public function items(): HasMany
{
return $this->hasMany(OrderItem::class);
}

public function medicines(): BelongsToMany
{
return $this->belongsToMany(Medication::class, 'order_items')
->withPivot(['quantity', 'price', 'discount'])
->withTimestamps();
}

}
