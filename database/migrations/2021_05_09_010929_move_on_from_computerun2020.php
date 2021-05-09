<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MoveOnFromComputerun2020 extends Migration
{
    /**
     * Helper function to remove fields
     */
    private function removeFields(Array $fields){
        foreach($fields as $field) DB::table('fields')->where('id', $field)->delete();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add new fields to 'events'
        if (Schema::hasTable('events')) Schema::table('events', function (Blueprint $table) {
            $table->boolean('private')->default(true);
            $table->unsignedBigInteger('cover_image')->nullable();
            $table->foreign('cover_image')->references('id')->on('files');
        });

        // Add new table
        if (!Schema::hasTable('event_permissions')) Schema::create('event_permissions', function (Blueprint $table) {
            $table->integer('event_id')->unsigned();
            $table->foreign('event_id')->references('id')->on('events');
            $table->string('field_id');
            $table->foreign('field_id')->references('id')->on('fields');
            $table->boolean('required');
            $table->string('validation_rule')->nullable();
            $table->primary(['event_id', 'field_id']);
        });

        // Change how admins are stored
        // Current admins will be promoted as global admins
        // Current committees will be promoted as global committee
        $fields = DB::table('fields');
        $fields->insert(['id' => 'role.administrator', 'name' => 'System Administrator', 'editable' => false]);
        $fields->insert(['id' => 'role.committee', 'name' => 'System-Wide Committee', 'editable' => false]);

        $users = DB::table('users')->where('university_id', 2)->orWhere('university_id', 3)->get();
        foreach ($users as $user){
            DB::table('user_properties')->insert(['user_id' => $user->id, 'field_id' => ($user->university_id == 2 ? 'role.administrator' : 'role.committee'), 'value' => 1]);
        }

        // Create new table for event-specific admins and committees
        if (!Schema::hasTable('event_roles')) Schema::create('event_roles', function (Blueprint $table) {
            $table->integer('event_id')->unsigned();
            $table->foreign('event_id')->references('id')->on('events');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('role')->nullable();
            $table->string('system_role');
            $table->foreign('system_role')->references('id')->on('fields');
            $table->primary(['event_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_roles');

        Schema::dropIfExists('event_permissions');

        if (Schema::hasTable('events')){
            Schema::table('events', function (Blueprint $table) {
                $table->dropForeign(['cover_image']);
            });
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('private');
                $table->dropColumn('cover_image');
            });
        }

        $users = DB::table('user_properties')->where('field_id', 'role.administrator')->orWhere('field_id', 'role.committee')->get();
        foreach ($users as $user){
            if ($user->field_id == 'role.administrator') DB::table('users')->where('user_id', $user->user_id)->update([
                'university_id' => 2
            ]);
            else DB::table('users')->where('user_id', $user->user_id)->update([
                'university_id' => 3
            ]);
        }

        $this->removeFields(['role.administrator', 'role.committee']);
    }
}
