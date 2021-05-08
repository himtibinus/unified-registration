<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDatabaseV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create 'universities' table
        if (!Schema::hasTable('universities')) Schema::create('universities', function (Blueprint $table) {
            $table->increments('id');
            $table->text('name');
        });
        // Add "Uncategorized
        DB::table('universities')->insert(['name' => 'None / Uncategorized']);
        // Update the Laravel's 'users' table
        if (Schema::hasTable('users')) Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('university_id')->default(1);
            $table->foreign('university_id')->references('id')->on('universities');
            $table->boolean('binusian')->default(false);
            $table->bigInteger('nim')->nullable();
            $table->text('phone')->nullable();
            $table->text('line')->nullable();
            $table->text('whatsapp')->nullable();
            $table->text('id_mobile_legends')->nullable();
            $table->text('id_pubg_mobile')->nullable();
            $table->text('id_valorant')->nullable();
            $table->text('major')->nullable();
        });
        // Create 'tickets' table
        // if (!Schema::hasTable('tickets')) Schema::create('tickets', function (Blueprint $table) {
        //     $table->increments('id');
        //     $table->text('email')->unique();
        //     $table->text('password');
        //     $table->text('name');
        //     $table->unsignedInteger('university_id');
        //     $table->foreign('university_id')->references('id')->on('universities');
        //     $table->boolean('binusian')->default(false);
        //     $table->bigInteger('nim');
        //     $table->text('phone');
        //     $table->text('line')->nullable();
        //     $table->text('whatsapp')->nullable();
        // });
        // Create 'events' table
        if (!Schema::hasTable('events')) Schema::create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->text('name');
            $table->text('location')->nullable();
            $table->dateTime('date', 0);
            $table->unsignedInteger('price')->default(0);
            $table->boolean('opened')->default(false);
            $table->boolean('attendance_opened')->default(false);
            $table->boolean('attendance_is_exit')->default(false);
            $table->text('url_link')->nullable();
            $table->text('totp_key');
            $table->unsignedInteger('seats')->default(0);
            $table->unsignedInteger('slots')->default(1);
            $table->unsignedInteger('team_members')->default(0);
            $table->unsignedInteger('team_members_reserve')->default(0);
            $table->integer('files')->default(0);
        });
        // Create 'teams' table
        if (!Schema::hasTable('teams')) Schema::create('teams', function (Blueprint $table) {
            $table->increments('id');
            $table->text('name');
            $table->unsignedInteger('event_id');
            $table->foreign('event_id')->references('id')->on('events');
            $table->integer('score')->default(0);
            $table->text('remarks')->nullable();
        });
        // Create 'registration' table
        if (!Schema::hasTable('registration')) Schema::create('registration', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('ticket_id');
            $table->foreign('ticket_id')->references('id')->on('users');
            $table->unsignedInteger('event_id');
            $table->foreign('event_id')->references('id')->on('events');
            $table->unsignedInteger('team_id')->nullable();
            $table->foreign('team_id')->references('id')->on('teams');
            $table->integer('status');
            $table->text('remarks')->nullable();
            $table->text('payment_code')->nullable();
        });
        // Create 'attendance' table
        if (!Schema::hasTable('attendance')) Schema::create('attendance', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('entry_timestamp')->nullable();
            $table->dateTime('exit_timestamp')->nullable();
            $table->unsignedInteger('registration_id');
            $table->foreign('registration_id')->references('id')->on('registration');
            $table->text('remarks');
        });
        // Add ADMIN and Committee
        DB::table('universities')->insert(['name' => 'COMPUTERUN 2020 System Administrator']);
        DB::table('universities')->insert(['name' => 'COMPUTERUN 2020 Official Committee']);
        // Add BINUS
        DB::table('universities')->insert(['name' => 'BINUS University - Universitas Bina Nusantara']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('registration');
        Schema::dropIfExists('teams');
        // Schema::dropIfExists('tickets');
        if (Schema::hasTable('users')){
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['university_id']);
            });
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['university_id', 'binusian', 'nim', 'phone', 'line', 'whatsapp', 'id_mobile_legends', 'id_pubg_mobile', 'id_valorant']);
            });
        }
        Schema::dropIfExists('events');
        Schema::dropIfExists('universities');
    }
}
