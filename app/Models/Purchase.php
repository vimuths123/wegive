<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'product_id', 'first_name', 'last_name', 'email', 'phone'];

    public static function boot()
    {
        parent::boot();

        self::created(function ($model) {
            $currentLogin = auth()->user()->currentLogin;
            activity()->causedBy($currentLogin)->performedOn($model->product)->log("{$model->user->name} purchased product, {$model->product->name}");
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
