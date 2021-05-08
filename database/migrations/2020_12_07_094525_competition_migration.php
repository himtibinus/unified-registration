<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CompetitionMigration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Modify 'files' to include 'answer_path'
        Schema::table('files', function (Blueprint $table) {
            $table->text('answer_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert the changes
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('answer_path');
        });
    }
}
