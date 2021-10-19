<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePayoutsTable extends Migration {

  public function up() {
    Schema::create('payouts', function (Blueprint $table) {
      $table->increments('id');
      $table->integer('user_id')->unsigned()->nullable();
      $table->float('amount')->unsigned()->nullable();
      $table->float('amount_payed_rub')->unsigned()->nullable();
      $table->tinyInteger('type')->unsigned()->nullable();
      $table->tinyInteger('status')->unsigned()->default(0); // 0 - new, 2 - paid, 3 - rejected
      $table->timestamp('status_updated_at', 0)->nullable();
      $table->string('payment_details')->nullable();
      $table->timestamps();

      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
  }

  public function down() {
    Schema::dropIfExists('payouts');
  }
}
