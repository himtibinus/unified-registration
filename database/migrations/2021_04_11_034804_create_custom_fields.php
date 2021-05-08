<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fields', function (Blueprint $table) {
            $table->string("id");
            $table->text("name");
            $table->text("category");
            $table->boolean("editable");
            $table->primary("id");
        });

        Schema::create('user_properties', function (Blueprint $table) {
            $table->bigInteger("user_id")->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->string("field_id");
            $table->foreign('field_id')->references('id')->on('fields');
            $table->text("value");
            $table->primary(["user_id", "field_id"]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_properties');

        Schema::dropIfExists('fields');
    }
}
