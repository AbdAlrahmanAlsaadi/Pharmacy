<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $fillable = ['user_id', 'medicine_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function Medication()
    {
        return $this->belongsTo(Medication::class);
    }

}
