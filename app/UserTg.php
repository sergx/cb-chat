<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTg extends Model {
  public $table = "users_tg";

  protected $guarded = [];

  public function BasicUser() {
    return $this->belongsTo("App\User", "basic_user_id", "id");
  }
}
