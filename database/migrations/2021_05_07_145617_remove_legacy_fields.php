<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveLegacyFields extends Migration
{
    /**
     * Helper function to migrate old fields to new format
     */
    private function updateToNewFormat(stdClass $user, Array $current_properties, String $old_value, String $new_value){
        if (isset($user->$old_value) && strlen($user->$old_value) > 0 && !isset($current_properties[$new_value])) DB::table('user_properties')->insert(['user_id' => $user->id, 'field_id' => $new_value, 'value' => $user->$old_value]);
    }

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
        // Rename unused 'category' on 'fields'
        Schema::table('fields', function (Blueprint $table) {
            $table->renameColumn('category', 'icon');
        });
        Schema::table('fields', function (Blueprint $table) {
            $table->string('icon')->nullable(false)->default('bs.gear')->change();
        });

        // Add items to 'fields' table
        $fields = DB::table('fields');
        $fields->insert(['id' => 'accounts.moonton.mobile_legends', 'name' => 'Mobile Legends: Bang Bang User ID', 'editable' => true]);
        $fields->insert(['id' => 'accounts.riot.valorant', 'name' => 'Valorant User ID', 'editable' => true]);
        $fields->insert(['id' => 'accounts.tencent.pubg_mobile', 'name' => 'PUBG Mobile User ID', 'editable' => true]);
        $fields->insert(['id' => 'accounts.valve.dota2', 'name' => 'DOTA 2 User ID', 'editable' => true]);
        try {
            $fields->insert(['id' => 'binusian.community_service_hours', 'name' => 'Current BINUSIAN Community Service Hours', 'editable' => false]);
            $fields->insert(['id' => 'binusian.regional', 'name' => 'BINUSIAN Regional / Campus Location', 'editable' => false]);
            $fields->insert(['id' => 'binusian.sat', 'name' => 'Current BINUSIAN SAT points', 'editable' => false]);
            $fields->insert(['id' => 'binusian.scu', 'name' => 'Current BINUSIAN SCU/IPK', 'editable' => false]);
            $fields->insert(['id' => 'binusian.sdc.training.basic.om', 'name' => 'LKMM PRIME/"Optimizing Me" training date', 'editable' => false]);
            $fields->insert(['id' => 'binusian.sdc.training.basic.taoc', 'name' => 'LKMM COMMET/"The Art of Communication" training date', 'editable' => false]);
            $fields->insert(['id' => 'binusian.sdc.training.intermediate', 'name' => 'LKMM Intermediate" training date', 'editable' => false]);
            $fields->insert(['id' => 'binusian.sdc.training.advanced', 'name' => 'LKMM Advanced" training date', 'editable' => false]);
            $fields->insert(['id' => 'binusian.year', 'name' => 'BINUSIAN Year', 'editable' => false]);
        } catch (Illuminate\Database\QueryException $e){
            printf("BINUSIAN-related fields already exists!\n");
        }
        $fields->insert(['id' => 'contacts.phone', 'name' => 'Phone Number', 'editable' => true]);
        $fields->insert(['id' => 'contacts.instagram', 'name' => 'Instagram registered username', 'editable' => true]);
        $fields->insert(['id' => 'contacts.line', 'name' => 'LINE registered phone number or ID', 'editable' => true]);
        $fields->insert(['id' => 'contacts.telegram', 'name' => 'Telegram registered phone number or username', 'editable' => true]);
        $fields->insert(['id' => 'contacts.twitter', 'name' => 'Twitter registered username', 'editable' => true]);
        $fields->insert(['id' => 'contacts.whatsapp', 'name' => 'WhatsApp registered phone number', 'editable' => true]);
        $fields->insert(['id' => 'university.nim', 'name' => 'Student ID / NIM', 'editable' => false]);
        $fields->insert(['id' => 'university.major', 'name' => 'Major / Study Program', 'editable' => false]);

        // Update the Laravel's 'users' table
        $query = DB::table('users')->select(['id', 'binusian', 'nim', 'phone', 'line', 'whatsapp', 'id_mobile_legends', 'id_pubg_mobile', 'id_valorant', 'major'])->get();
        foreach ($query as $user){
            // Get current user properties and map them into a new array
            $query2 = DB::table('user_properties')->where('user_id', $user->id)->get();
            $current_properties = [];
            for ($i = 0; $i < count($query2); $i++){
                $current_properties[$query2[$i]->field_id] = $query2[$i]->value;
            }

            // Automatically update new fields which was not set before
            if (isset($user->binusian) && $user->binusian == true && !isset($current_properties['binusian.year'])) DB::table('user_properties')->insert(['user_id' => $user->id, 'field_id' => 'binusian.year', 'value' => '20' . substr($user['nim'], 0, 2)]);

            $this->updateToNewFormat($user, $current_properties, 'major', 'university.major');
            $this->updateToNewFormat($user, $current_properties, 'nim', 'university.nim');

            $this->updateToNewFormat($user, $current_properties, 'phone', 'contacts.phone');
            $this->updateToNewFormat($user, $current_properties, 'line', 'contacts.line');
            $this->updateToNewFormat($user, $current_properties, 'whatsapp', 'contacts.whatsapp');

            $this->updateToNewFormat($user, $current_properties, 'id_mobile_legends', 'accounts.moonton.mobile_legends');
            $this->updateToNewFormat($user, $current_properties, 'id_pubg_mobile', 'accounts.tencent.pubg_mobile');
            $this->updateToNewFormat($user, $current_properties, 'id_valorant', 'accounts.riot.valorant');
        }

        // Drop columns
        Schema::dropColumns('users', ['binusian', 'nim', 'phone', 'line', 'whatsapp', 'id_mobile_legends', 'id_pubg_mobile', 'id_valorant', 'major']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Update the Laravel's 'users' table
        if (Schema::hasTable('users')) Schema::table('users', function (Blueprint $table) {
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

        // Update the 'user_properties table
        $user_properties = DB::table('user_properties');
        $query = $user_properties->get();
        foreach ($query as $property){
            $user = DB::table('users')->where('id', $property->user_id);
            switch ($property->field_id){
                case 'university.major':
                    $user->update(['major' => $property->value]);
                    break;
                case 'university.nim':
                    $user->update(['nim' => $property->value]);
                    break;
                case 'contacts.phone':
                    $user->update(['phone' => $property->value]);
                    break;
                case 'contacts.line':
                    $user->update(['line' => $property->value]);
                    break;
                case 'contacts.whatsapp':
                    $user->update(['whatsapp' => $property->value]);
                    break;
                case 'accounts.moonton.mobile_legends':
                    $user->update(['id_mobile_legends' => $property->value]);
                    break;
                case 'accounts.riot.valorant':
                    $user->update(['id_valorant' => $property->value]);
                    break;
                case 'accounts.tencent.pubg_mobile':
                    $user->update(['id_pubg_mobile' => $property->value]);
                    break;
                default: continue 2;
            }
        }

        $user_properties->where('field_id', 'university.major')
            ->orWhere('field_id', 'university.nim')
            ->orWhere('field_id', 'contacts.phone')
            ->orWhere('field_id', 'contacts.line')
            ->orWhere('field_id', 'contacts.whatsapp')
            ->orWhere('field_id', 'accounts.moonton.mobile_legends')
            ->orWhere('field_id', 'accounts.riot.valorant')
            ->orWhere('field_id', 'accounts.tencent.pubg_mobile')
            ->delete();

        // Query all users to check their BINUSIAN status
        $query = DB::table('users')->get();
        foreach ($query as $user){
            if ($user->university_id > 1 && $user->university_id <= 4) DB::table('users')->where('id', $user->id)->update(['binusian' => true]);
        }

        // Remove field definitions
        // Add items to 'fields' table
        $this->removeFields([
            'accounts.moonton.mobile_legends', 'accounts.riot.valorant', 'accounts.tencent.pubg_mobile', 'accounts.valve.dota2',
            'contacts.phone', 'contacts.instagram', 'contacts.line', 'contacts.telegram', 'contacts.twitter', 'contacts.whatsapp',
            'university.nim', 'university.major'
        ]);

        // Re-add 'category' to 'fields'
        Schema::table('fields', function (Blueprint $table) {
            $table->renameColumn('icon', 'category');
        });
    }
}
