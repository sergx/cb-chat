<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable {
  use Notifiable;

  protected $guarded = [
    'remember_token',
    'email_verified_at',
  ];

  protected $hidden = [
    'password',
    'remember_token',
  ];

  public function Tg(){
    return $this->hasOne("App\UserTg", "basic_user_id", "id");
  }

  public function Cb(){
    return $this->hasOne("App\UserCb", "basic_user_id", "id");
  }

  public function Payouts(){
    return $this->hasMany("App\Payout", "user_id", "id");
  }

}
