<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOptionCacheTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('option_caches', function (Blueprint $table) {
           $table->increments('id');
           $table->string('option');
           $table->string('status');
           $table->string('ticket');
           $table->timestamps();
           $table->bigInteger('update_time');//device time
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
