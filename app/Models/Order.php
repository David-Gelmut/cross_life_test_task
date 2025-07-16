<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Order extends Model
{
    protected $fillable = ['number', 'status', 'date', 'user_id'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
