<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = ['number', 'status', 'date', 'user_id'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot(['price','quantity']);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
