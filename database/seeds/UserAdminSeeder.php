<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserAdminSeeder extends Seeder {

  public function run() {
    // php artisan db:seed --class=UserAdminSeeder
    // create - создает записи в БД, а make - создает класс, то есть требует требует сохранения после этого

    factory(App\User::class)->create([
      'name' => 'Сергей',
      'email' => 'serg_x@bk.ru',
      'role' => 'superadmin',
      'password' => bcrypt('123456789'),
    ])->each(function ($user) {
      $user->Tg()->save(factory(App\UserTg::class)->make([
        'id' => 337506768,
        'basic_user_id' => 1,
        'username' => "khiliuta",
      ]));
      $user->Cb()->save(factory(App\UserCb::class)->make([
        //'id' => 337506768,
        'basic_user_id' => 1,
        'username' => "crypto_xxx",
      ]));
    });
  }
}
