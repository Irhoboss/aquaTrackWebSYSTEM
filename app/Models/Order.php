<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {
 protected $fillable=['user_id','order_number','delivery_token','status','total','customer_name','phone','delivery_address','note','delivered_at','delivered_by'];
 protected $casts=['total'=>'decimal:2','delivered_at'=>'datetime'];
 public function items(){return $this->hasMany(OrderItem::class);}
 public function customer(){return $this->belongsTo(User::class,'user_id');}
 public function rider(){return $this->belongsTo(User::class,'delivered_by');}
}
