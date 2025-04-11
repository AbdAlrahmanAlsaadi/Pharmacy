<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    protected $fillable=['scientific_name','commercial_name','category_id',
    'manufacturer','quantity','expiry_date','price'];




public function category(){
return $this->belongsTo(category::class);

}

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites')
            ->withTimestamps();
    }
}
