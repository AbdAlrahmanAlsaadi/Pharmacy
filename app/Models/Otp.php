<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable=['user_id','code','expires_at','verified'];


public function isValid()
{
    return !$this->verified && $this->expires_at > now();
}

public function markAsVerified()
{
    $this->update(['verified' => true]);
}
public function user()
{
    return $this->belongsTo(User::class);
}
}
