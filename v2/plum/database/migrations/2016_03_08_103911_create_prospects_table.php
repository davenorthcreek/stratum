<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProspectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prospects', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('candidate');
            $table->text('form_response');
            $table->integer('bullhorn_id')->unique;
            $table->integer('owner_id');
            $table->timestamp('form_sent');
            $table->timestamp('form_returned');
            $table->timestamp('form_approved');
            $table->timestamp('bullhorn_updated');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('prospects');
    }
}
