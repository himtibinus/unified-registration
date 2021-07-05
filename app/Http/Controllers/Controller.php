<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private function checkAdminOrCommittee($userId, $eventId){
        $admin = false;
        $committee = false;

        // Check whether the user is an admin or comittee
        $select = DB::table('user_properties')->where('user_id', $userId);
        $query = $select->where('field_id', 'role.administrator')->first();
        if ($query && $query->value == '1'){
            $admin = true;
            $committee = true;
        }
        $select = DB::table('user_properties')->where('user_id', $userId);
        $query = $select->where('field_id', 'role.committee')->first();
        if ($query && $query->value == '1'){
            $committee = true;
        }
        if (!$admin && !$committee && $eventId > 0){
            // Check whether Event ID exists
            $select = DB::table('events')->where('id', $eventId)->first();
            if ($select){
                $select = DB::table('event_roles')->where('event_id', $eventId);
                $query = $select->where('user_id', $userId)->where('system_role', 'role.administrator')->first();
                if ($query){
                    $admin = true;
                    $committee = true;
                }
                $select = DB::table('event_roles')->where('event_id', $eventId);
                $query = $select->where('user_id', $userId)->where('system_role', 'role.committee')->first();
                if ($query){
                    $committee = true;
                }
            }
        }

        return (object) [
            'admin' => $admin,
            'committee' => $committee
        ];
    }
}
