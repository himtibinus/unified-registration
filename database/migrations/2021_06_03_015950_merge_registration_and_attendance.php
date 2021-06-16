<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeRegistrationAndAttendance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update "registration" database
        Schema::table('registration', function (Blueprint $table) {
            $table->dateTime('check_in_timestamp')->nullable();
            $table->dateTime('check_out_timestamp')->nullable();
        });

        // Merge all "attendance" to "registration"
        $attendances = DB::table('attendance')->get();

        foreach ($attendances as $attendance){
            $registration = DB::table('registration')->where('id', $attendance->registration_id)->first();

            if ($registration->check_in_timestamp == null || strtotime($attendance->entry_timestamp) < strtotime($registration->check_in_timestamp)){
                DB::table('registration')->where('id', $attendance->registration_id)->update(['check_in_timestamp' => $attendance->entry_timestamp]);
            }

            if ($registration->check_out_timestamp == null || strtotime($attendance->exit_timestamp) < strtotime($registration->check_out_timestamp)){
                DB::table('registration')->where('id', $attendance->registration_id)->update(['check_out_timestamp' => $attendance->exit_timestamp]);
            }

            if (strlen($registration->remarks) == 0 || $registration->remarks == 'Late'){
                DB::table('registration')->where('id', $attendance->registration_id)->update(['check_out_timestamp', $attendance->remarks]);
            }
        }

        // Delete columns
        Schema::dropIfExists('attendance');

        $registrations = DB::table('registration')->get();

        foreach ($registrations as $registration){
            $check_in_length = strlen($registration->check_in_timestamp);
            $check_out_length = strlen($registration->check_out_timestamp);
            if ($check_in_length + $check_out_length == 0) continue;
            $status = 4;
            $remarks = 'On Time';
            if ($check_in_length == 0 && $check_out_length > 0) $remarks = 'Late';
            if ($check_in_length > 0 && $check_out_length > 0 && $registration->remarks != 'Late') $status = 5;

            DB::table('registration')->where('id', $registration->id)->update(['status' => $status, 'remarks' => $remarks]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Recreate 'attendance' table
        if (!Schema::hasTable('attendance')) Schema::create('attendance', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('entry_timestamp')->nullable();
            $table->dateTime('exit_timestamp')->nullable();
            $table->unsignedInteger('registration_id');
            $table->foreign('registration_id')->references('id')->on('registration');
            $table->text('remarks');
        });

        // Put all 'registration' timestamp inside 'attendance'
        $registrations = DB::table('registration')->orderBy('check_in_timestamp', 'asc')->get();
        foreach($registrations as $registration){
            $check_in_length = strlen($registration->check_in_timestamp);
            $check_out_length = strlen($registration->check_out_timestamp);
            if ($check_in_length + $check_out_length == 0) continue;
            DB::table('attendance')->insert(['registration_id' => $registration->id, 'entry_timestamp' => $registration->check_in_timestamp, 'exit_timestamp' => $registration->check_out_timestamp, 'remarks' => $registration->remarks]);
        }

        Schema::table('registration', function (Blueprint $table) {
            $table->dropColumn('check_in_timestamp');
            $table->dropColumn('check_out_timestamp');
        });
    }
}
