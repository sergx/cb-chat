<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTgTable extends Migration {
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up() {
    Schema::create('users_tg', function (Blueprint $table) {
      $table->bigInteger('id');
      //$table->foreignId('basic_user_id')->unsigned()->nullable()->constrained('users')->onDelete('cascade');
      $table->integer('basic_user_id')->unsigned()->nullable();
      $table->string('username');
      $table->timestamps();

      // index
      $table->primary('id');
      $table->unique('basic_user_id');
      //$table->integer('basic_user_id')->unsigned()->nullable();
      $table->foreign('basic_user_id')->references('id')->on('users')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down() {
    Schema::dropIfExists('users_tg');
  }
}
