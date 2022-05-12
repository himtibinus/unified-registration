<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnlineOfflineStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('events')) Schema::table('events', function (Blueprint $table) {
            $table->Integer('event_offline_status');
            $table->unsignedInteger('offline_seats')->default(0);
        });
        if (Schema::hasTable('registration')) Schema::table('registration', function (Blueprint $table) {
            $table->Integer('offline_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('events')) Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('event_offline_status');
            $table->dropColumn('offline_seats');
        });
        if (Schema::hasTable('registration')) Schema::table('registration', function (Blueprint $table) {
            $table->dropColumn('offline_status');
        });
    }
}