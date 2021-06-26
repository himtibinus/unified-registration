<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEventGroupMigration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create "Event Group" table
        if (!Schema::hasTable('event_groups')) Schema::create('event_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->text('name');
            $table->longText('description')->nullable();
            $table->text('url')->nullable();
            $table->mediumText('cover_image')->nullable();
        });

        // Alter "Events" table
        if (Schema::hasTable('events')) Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('event_group_id')->nullable();
            $table->foreign('event_group_id')->references('id')->on('event_groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Alter "Events" table
        if (Schema::hasTable('events')) Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('event_group_id');
        });

        // Drop "Event Group" table
        Schema::dropIfExists('event_group');
    }
}
