<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model {
  //
  public $table = "payouts";

  protected $guarded = [];

  public function BasicUser() {
    return $this->belongsTo("App\User", "user_id", "id");
  }
}
