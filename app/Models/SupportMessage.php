<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SupportMessage extends Model { protected $fillable=['user_id','sender_id','body','is_staff_reply']; protected $casts=['is_staff_reply'=>'boolean']; public function sender(){return $this->belongsTo(User::class,'sender_id');} public function customer(){return $this->belongsTo(User::class,'user_id');} }
