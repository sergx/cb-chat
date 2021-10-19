<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserCb extends Model
{
  public $table = "users_cb";

  protected $guarded = [];

  public function BasicUser() {
    return $this->belongsTo("App\User");
  }
}
