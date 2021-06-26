<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddQueueMigration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create "Clients" table, consisting of list of valid clients
        if (!Schema::hasTable('attendance_clients')) Schema::create('attendance_clients', function (Blueprint $table) {
            $table->string('id');
            $table->text('name');
            $table->boolean('enabled')->default(false);
            $table->primary('id');
        });
        // Create "Emails" queue
        if (!Schema::hasTable('email_queue')) Schema::create('email_queue', function (Blueprint $table) {
            $table->increments('id');
            $table->text('email');
            $table->text('subject');
            $table->longText('message');
            $table->text('status')->default('PENDING');
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
        });
        // Create "Attendance" queue
        if (!Schema::hasTable('attendance_queue')) Schema::create('attendance_queue', function (Blueprint $table) {
            $table->increments('id');
            $table->string('attendance_client_id');
            $table->text('email');
            $table->text('totp_key');
            $table->text('status')->default('PENDING');
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
            $table->foreign('attendance_client_id')->references('id')->on('attendance_clients');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_queue');

        Schema::dropIfExists('email_queue');

        Schema::dropIfExists('attendance_clients');
    }
}
