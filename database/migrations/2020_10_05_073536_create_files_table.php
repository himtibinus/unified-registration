<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create the "files" table
        if (!Schema::hasTable('files')) Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('name');
        });

        // Create the "payments" table (Pembayaran)
        if (!Schema::hasTable('payments')) Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('payee');
            $table->text('payment_method');
            $table->text('payment_account');
            $table->unsignedBigInteger('file_id')->nullable();
            $table->foreign('file_id')->references('id')->on('files');
            $table->integer('status')->default(0);
        });

        // Update the "registration" table to include files
        Schema::table('registration', function (Blueprint $table) {
            $table->unsignedBigInteger('file_id')->nullable();
            $table->foreign('file_id')->references('id')->on('files');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->foreign('payment_id')->references('id')->on('payments');
        });

        // Create the "kyc" table (Know Your Customer - Verifikasi KTM)
        if (!Schema::hasTable('kyc')) Schema::create('kyc', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->foreign('ticket_id')->references('id')->on('users');
            $table->unsignedBigInteger('file_id')->nullable();
            $table->foreign('file_id')->references('id')->on('files');
            $table->integer('status')->default(0);
        });

        // Update the "users" table to include NIMs from other universities
        Schema::table("users", function (Blueprint $table){
            // Change NIM to support texts and longer integers
            $table->text('nim')->nullable()->change();
            // Add isVerified
            $table->integer('verified')->default(0);
        });

        // Add Certificates
        if (!Schema::hasTable('certificates')) Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->foreign('ticket_id')->references('id')->on('users');
            $table->unsignedInteger('event_id')->nullable();
            $table->foreign('event_id')->references('id')->on('events');
            $table->unsignedBigInteger('file_id')->nullable();
            $table->foreign('file_id')->references('id')->on('files');
            $table->integer('status')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('certificates');

        if (Schema::hasTable('users')) Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['verified']);
            $table->bigInteger('nim')->nullable()->change();
        });

        Schema::dropIfExists('kyc');

        if (Schema::hasTable('registration')){
            Schema::table('registration', function (Blueprint $table) {
                $table->dropForeign(['file_id']);
                $table->dropForeign(['payment_id']);
            });
            Schema::table('registration', function (Blueprint $table) {
                $table->dropColumn(['file_id', 'payment_id']);
            });
        }

        Schema::dropIfExists('payments');

        Schema::dropIfExists('files');
    }
}
