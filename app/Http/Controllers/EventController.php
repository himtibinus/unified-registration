<?php

namespace App\Http\Controllers;

use App\Mail\SendInvoice;
use App\Mail\SendNewTeamNotification;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use DateTime;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class EventController extends Controller
{
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
                $query = $select->where('system_role', 'role.administrator')->first();
                if ($query && $query->value == '1'){
                    $admin = true;
                    $committee = true;
                }
                $select = DB::table('event_roles')->where('event_id', $eventId);
                $query = $select->where('system_role', 'role.committee')->first();
                if ($query && $query->value == '1'){
                    $committee = true;
                }
            }
        }

        return (object) [
            'admin' => $admin,
            'committee' => $committee
        ];
    }

    /**
     * Utility function to ensure that the user has been authenticated
     */
    private function requiresLogin(string $path, int $eventId, bool $for_admin, bool $for_committee){
        if (!Auth::check()){
            Session::put('error', 'Please log in before continuing to this page');
            Session::put('loginTo', $path);
            return false;
        }

        $check = $this->checkAdminOrCommittee(Auth::id(), $eventId);

        if (($check->admin && $for_admin) || ($check->committee && $for_committee) == true) return true;
        Session::put('error', 'You are not authorized to access this page');
        return false;
    }

    public function validateFields($event_permissions, $user_properties)
    {
        $eligible_to_register = true;
        for ($i = 0, $j = 0; $i < count($event_permissions) && $j < count($user_properties); $i++, $j++){
            $diff = strcmp($event_permissions[$i]->field_id, $user_properties[$j]->field_id);
            // If equal
            if ($diff == 0){
                $event_permissions[$i]->current_value = $user_properties[$j]->value;
                // Validate with regex
                if (strlen($event_permissions[$i]->validation_rule) == 0 || preg_match($event_permissions[$i]->validation_rule, $user_properties[$j]->value)){
                    $event_permissions[$i]->satisfied = true;
                } else {
                    $eligible_to_register = false;
                }
            }
            // If $user_properties[$j]'s ASCII difference is less than $event_permissions[$i]
            else if ($diff > 0){
                // Try to repeat the loop
                // Increment $j but not $i
                $i--;
            }
            // Else if the field is not found
            else {
                // Is the field required or optional?
                if ($event_permissions[$i]->required){
                    $eligible_to_register = false;
                }
                // Increment $i but not $j
                $j--;
            }
        }
        // If the last required permissions is not satisfied
        if ($i > 0 && !isset($event_permissions[$i - 1]->satisfied) && $event_permissions[$i - 1]->required){
            $eligible_to_register = false;
        }
        // Check whether there are validations which have not been checked
        if ($i < count($event_permissions)) for (; $i < count($event_permissions); $i++){
            if ($event_permissions[$i]->required){
                $eligible_to_register = false;
            }
        }
        // Return a new stdClass
        return (object) [
            'eligible_to_register' => $eligible_to_register,
            'event_permissions' => $event_permissions
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Redirect to home
        return redirect('/home');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        date_default_timezone_set('UTC');
        $path = $request->url();
        if (!$this->requiresLogin($path, 0, true, false)) return redirect('home');

        $key = '';
        for ($i = 0; $i < 6; $i++) $key .= rand(0, 9);

        // Create a new event with random totp_key
        $id = DB::table('events')->insertGetId(['name' => 'Untitled Event', 'totp_key' => $key, 'date' => date("Y-m-d H:i:s")]);
        return redirect('/events/' . $id);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        // Check whether the event exists
        $event = DB::table('events')->where('id', $id)->first();
        if (!$event){
            Session::put('error', 'The event you requested is not found.');
            return redirect('home');
        }

        // Set these variables to null to avoid querying some unnecessary data
        $user = null;
        $registrations = [];
        $event_permissions = null;
        $user_properties = null;

        // Check whether the user has been logged in and registered
        if (Auth::check()){
            $user = Auth::user();
            $registrations = DB::table('registration')->where('ticket_id', $user->id)->where('event_id', $event->id)->where('status', '!=', 1)->get();
        }

        $admin_or_committee = $this->requiresLogin($request->url(), $event->id, true, true);
        Session::remove('error');

        // Check whether the event is private
        if ($event->private && !$admin_or_committee){
            // Check whether the user has been registered to that event
            if (count($registrations) == 0){
                Session::put('error', 'The event you requested is not found.');
                return redirect('home');
            }
        }

        $validation = (object) [
            'event_permissions' => [],
            'eligible_to_register' => false
        ];

        // If the registration is opened
        if ($event->opened){
            // Check permissions and validation
            // Load from cache
            $event_permissions = Cache::get('event_permissions_' . $event->id, []);
            if (count($event_permissions) == 0){
                $event_permissions = DB::table('event_permissions')->join('fields', 'event_permissions.field_id', 'fields.id')->where('event_id', $event->id)->get();
                Cache::put('event_permissions_' . $event->id, $event_permissions, 300);
            }

            if ($user){
                $user_properties = DB::table('user_properties')->where('user_id', $user->id)->get();
                $validation = $this->validateFields($event_permissions, $user_properties);
            }
        }

        if ($event->slots - count($registrations) <= 0) $validation->eligible_to_register = false;

        // Return view
        return view('event-details', ['event' => $event, 'user' => $user, 'registrations' => $registrations, 'admin_or_committee' => $admin_or_committee, 'event_permissions' => $validation->event_permissions, 'eligible_to_register' => $validation->eligible_to_register]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        // Make sure that the user is an admin or committee
        if (!Auth::check()){
            $request->session()->put('error', 'Please log in to continue.');
            return redirect('home');
        }

        // Check whether the event exists
        $event = DB::table('events')->where('id', $id)->first();
        if (!$event){
            $request->session()->put('error', 'This event does not exist.');
            return redirect('home');
        }

        // Check whether the user is an admin or committee
        $check = $this->checkAdminOrCommittee(Auth::id(), $id);
        if (!$check->admin && !$check->committee){
            // If not simply return to main page
            return redirect('events/' . $id);
        }

        // Gather the data
        $data = DB::table('registration')->select('registration.*', 'attendance.entry_timestamp', 'attendance.exit_timestamp', 'users.name', 'users.email', 'users.verified', 'users.email_verified_at', 'users.university_id', 'users.created_at', 'users.updated_at')->join('users', 'users.id', 'registration.ticket_id')->join('attendance', 'registration.id', '=', 'attendance.registration_id', 'left outer')->where('event_id', $id)->get();

        // Return view
        return view('event-manager', ['event' => $event, 'registrations' => $data, 'role' => $check]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Make sure that it's an Admin (Higher Level)
        if (!$this->requiresLogin($request->path(), $id, true, false)) return redirect('home');

        foreach($request->all() as $key => $value) {
            if (Str::startsWith($key, "status-") && $value >= 0){
                $key = substr($key, 7);
                DB::table('registration')->where('id', $key)->update(['status' => $value]);
            } else if (Str::startsWith($key, "action-")) switch ($key){
                case "action-update-kicker":
                    if ($value != '') DB::table('events')->where('id', $id)->update(['kicker' => $value]);
                break;
                case "action-update-name":
                    if ($value != '') DB::table('events')->where('id', $id)->update(['name' => $value]);
                break;
                case "action-update-date":
                    if ($value != '') DB::table('events')->where('id', $id)->update(['date' => new DateTime($value)]);
                break;
                case "action-update-location":
                    if ($value != '') DB::table('events')->where('id', $id)->update(['location' => $value]);
                break;
                case "action-update-price":
                    if ($value != '') DB::table('events')->where('id', $id)->update(['price' => $value]);
                break;
                case "action-update-cover_image":
                    if ($value != '') DB::table('events')->where('id', $id)->update(['cover_image' => $value]);
                break;
                case "action-update-theme_color_foreground":
                    if ($value != '') DB::table('events')->where('id', $id)->update(['theme_color_foreground' => $value]);
                break;
                case "action-update-theme_color_background":
                    if ($value != '') DB::table('events')->where('id', $id)->update(['theme_color_background' => $value]);
                break;
                case "action-update-description_public":
                    if ($value != '') DB::table('events')->where('id', $id)->update(['description_public' => $value]);
                break;
                case "action-update-description_pending":
                    if ($value != '') DB::table('events')->where('id', $id)->update(['description_pending' => $value]);
                break;
                case "action-update-description_private":
                    if ($value != '') DB::table('events')->where('id', $id)->update(['description_private' => $value]);
                break;
                case "action-registration-status":
                    if ($value == "enabled") DB::table('events')->where('id', $id)->update(['opened' => 1]);
                    else if ($value == "disabled") DB::table('events')->where('id', $id)->update(['opened' => 0]);
                break;
                case "action-registration-private":
                    if ($value == "private") DB::table('events')->where('id', $id)->update(['private' => 1]);
                    else if ($value == "public") DB::table('events')->where('id', $id)->update(['private' => 0]);
                break;
                case "action-update-seats":
                    if ($value > 0) DB::table('events')->where('id', $id)->update(['seats' => $value]);
                break;
                case "action-update-slots":
                    if ($value > 0) DB::table('events')->where('id', $id)->update(['slots' => $value]);
                break;
                case "action-update-team_members":
                    if ($value > 0) DB::table('events')->where('id', $id)->update(['team_members' => $value]);
                break;
                case "action-update-team_members_reserve":
                    if ($value > 0) DB::table('events')->where('id', $id)->update(['team_members_reserve' => $value]);
                break;
                case "action-attendance-status":
                    if ($value == "enabled") DB::table('events')->where('id', $id)->update(['attendance_opened' => 1]);
                    else if ($value == "disabled") DB::table('events')->where('id', $id)->update(['attendance_opened' => 0]);
                break;
                case "action-attendance-type":
                    if ($value == "entrance") DB::table('events')->where('id', $id)->update(['attendance_is_exit' => 0]);
                    else if ($value == "exit") DB::table('events')->where('id', $id)->update(['attendance_is_exit' => 1]);
                break;
                case "action-update-url_link":
                    if ($value != '') DB::table('events')->where('id', $id)->update(['url_link' => $value]);
                break;
                case "action-update-totp_key":
                    if ($value != '') DB::table('events')->where('id', $id)->update(['totp_key' => $value]);
                break;
            }
            // Clear cache
            Cache::forget('availableEvents');
            $availableEvents = DB::table('events')->where('private', false)->where('opened', true)->get();
            Cache::put('availableEvents', $availableEvents, 300);

        }

        return redirect("/events/" . $id . '/edit');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Attendance Module
     */
    public function attendance(Request $request, $id)
    {
        date_default_timezone_set('UTC');
        // Check whether the user is logged in
        if (!Auth::check()) return response('You have not logged in', 401);

        // Check whether the user is registered
        $registration = DB::table('registration')->where('id', $id)->first();
        if (!$registration) return response('Ticket not found', 404);
        else if ($registration->status < 2) return response('Forbidden', 403);

        // Check event
        $event = DB::table('events')->where('id', $registration->event_id)->first();
        if ($event->attendance_opened) return response('Attendance period has been closed', 403);

        // Check attendance type
        $is_exit = $event->attendance_is_exit;

        // Check database
        $attendance = DB::table('attendance')->where('registration_id',$request->registration_id);
        $timestamp = Carbon::now();

        $exist = $attendance->first();

        if ($is_exit){
            // Get the exit token
            $token = $request->input('token');
            if (strlen($token) == 0 || $token != '' . $event->totp_key) return response('Incorrect token', 401);

            if ($exist){
                // Record exit attendance
                $attendance->update([
                    'exit_timestamp' => $timestamp,
                    'remarks' => 'Attended'
                ]);
                DB::table('registration')->where('id',$id)->update(['status' => 5]);
            } else {
                // Record new attendance
                $attendance->insert([
                    'exit_timestamp' => $timestamp,
                    'registration_id' => $id,
                    'remarks' => 'Late'
                ]);
                DB::table('registration')->where('id',$id)->update(['status' => 4]);
            }
        } else if (!$exist) {
            // Record new attendance
            DB::table('attendance')->insert([
                'entry_timestamp' => $timestamp,
                'registration_id' => $id,
                'remarks' => 'Attending'
            ]);
            DB::table('registration')->where('id',$id)->update(['status' => 4]);
        }
        return response()->json([
            'timestamp' => $timestamp,
            'attendanceType' => ($is_exit ? 'exit' : 'entrance'),
            'url' => $event->url_link
        ]);
    }

    // Module to get user details
    public function getUserDetails(Request $request){
        // Ensure that the user has logged in
        if (!Auth::check()) return response()->json(['error' => 'You are not authenticated']);
        // Ensure that the user has complete payload
        if (!$request->has('email') || !$request->has('eventId')) return response()->json(['error' => 'Incomplete Request']);
        $user = null;
        if ($request->input('email') == Auth::user()->email){
            if ($request->input('allowSelf') == false) return response()->json(['error' => 'You should not add yourself as a member']);
            $user = Auth::user();
        } else {
            // Search on database
            $user = DB::table('users')->where('email', $request->input('email'))->first();
        }

        if (!$user) return response()->json(['error' => 'User not found']);

        $event = DB::table('events')->where('id', $request->get('eventId'))->first();
        if (!$event) return response()->json(['error' => 'Event not found']);

        // Load from cache
        $event_permissions = Cache::get('event_permissions_' . $event->id, []);
        if (count($event_permissions) == 0){
            $event_permissions = DB::table('event_permissions')->join('fields', 'event_permissions.field_id', 'fields.id')->where('event_id', $event->id)->get();
            Cache::put('event_permissions_' . $event->id, $event_permissions, 300);
        }

        // Check permissions and validation
        $user_properties = DB::table('user_properties')->where('user_id', $user->id)->get();
        $validation = $this->validateFields($event_permissions, $user_properties);
        $registrations = DB::table('registration')->where('ticket_id', $user->id)->where('event_id', $request->get('eventId'))->where('status', '!=', 1)->get();

        if ($event->slots - count($registrations) <= 0) $validation->eligible_to_register = false;

        return response()->json([
            'name' => $user->name,
            'registrations' => $registrations,
            'eligibleToRegister' => $validation->eligible_to_register,
            'remainingSlots' => $event->slots - count($registrations),
            'eventPermissions' => $validation->event_permissions
        ]);
    }

    // Module to register to certain events
    public function registerToEvent(Request $request){
        if (!Auth::check()) return redirect("/home");

        // Get event ID
        $event_id = $request->input("event_id");
        $team_required = false;

        // Get the slot number
        $slots = ($request->has("slots") && $request->input("slots") > 1) ? $request->input("slots") : 1;

        // Set the Payment Code
        $payment_code = null;

        // Check on database whether the event exists
        $event = DB::table("events")->where("id", $event_id)->first();
        if (!$event){
            $request->session()->put('error', "Event not found.");
            return redirect('/home');
        } else if ($event->opened == 0) {
            $request->session()->put('error', "The registration period for " . $event->name . " has been closed.");
            return redirect('/home');
        } else if ($event->team_members + $event->team_members_reserve > 0) $team_required = true;

        if ($event->price > 0) $payment_code = uniqid();

        // Create an array of users to be validated
        $leader = Auth::user();
        $members = [];
        $reserve = [];

        // Get whether teams are needed
        if ($team_required == true){
            if (!$request->has("create_team") || !$request->has("team_name") || $request->input("team_name") == ""){
                $request->session()->put('error', "You will need to create a team for " . $event->name . ".");
                return redirect('/home');
            }

            // Team members
            for ($i = 0; $i < $event->team_members; $i++){
                if (!$request->has('team_member_' . $i)){
                    $request->session()->put('error', "Incomplete team members");
                    return redirect('/home');
                }
                array_push($members, DB::table('users')->where('email', 'team_member_' . $i));
            }

            // Reserve members
            for ($i = 0; $i < $event->team_members; $i++){
                if ($request->has('team_member_reserve_' . $i)) array_push($reserve, DB::table('users')->where('email', 'team_member_reserve_' . $i));
            }
        }

        // Validate users
        $queue = [$leader];
        $queue = array_merge($queue, $members, $reserve);
        $event_permissions = DB::table('event_permissions')->where('event_id', $event->id)->get();

        $validation_failed = 0;

        foreach ($queue as $user){
            $user_properties = DB::table('user_properties')->where('user_id', $user->id)->get();
            $registrations = DB::table('registration')->where('event_id', $event->id)->where('ticket_id', $user->id)->where('status', '!=', 1)->get();
            $validation = $this->validateFields($event_permissions, $user_properties);
            if ($event->slots - count($registrations) <= 0) $validation->eligible_to_register = false;

            if (!$validation->eligible_to_register){
                $validation_failed++;
            }
        }

        if ($validation_failed > 0){
            $request->session()->put('error', "You or your team members are not eligible to register to this event.");
            return redirect('/home');
        }

        if ($team_required == true){
            // Create a new team
            $team_id = DB::table("teams")->insertGetId(["name" => $request->input("team_name"), "event_id" => $event_id]);

            // Assign the database template
            $query = [];
            $draft = ["event_id" => $event_id, "status" => 0, "payment_code" => $payment_code, "team_id" => $team_id, "ticket_id" => null, "remarks" => null];

            // Assign the User ID of the team leader
            $tempdetails = json_decode(json_encode(Auth::user()), true);
            for ($j = 0; $j < $slots; $j++){
                $temp = $draft;
                $temp["ticket_id"] = $tempdetails["id"];
                $temp["remarks"] = "Team Leader";
                if ($slots > 1) $temp["remarks"] = $temp["remarks"] . ", Slot " . ($j + 1);
                array_push($query, $temp);
            }

            // Find the User ID of team members
            for ($i = 0; $i < $event->team_members; $i++){
                $tempdetails = json_decode(json_encode(DB::table("users")->where("email", $request->input("team_member_" . ($i + 1)))->first()), true);
                for ($j = 0; $j < $slots; $j++){
                    $temp = $draft;
                    echo(print_r($tempdetails));
                    $temp["ticket_id"] = $tempdetails["id"];
                    $temp["remarks"] = "Team Member";
                    if ($slots > 1) $temp["remarks"] = $temp["remarks"] . ", Slot " . ($j + 1);
                    array_push($query, $temp);
                }
                // Send Email
                Mail::to($request->input("team_member_" . ($i + 1)))->send(new SendNewTeamNotification(["name" => $tempdetails["name"], "team_name" => $request->input("team_name"), "team_id" => $team_id, "team_leader_name" => Auth::user()->name, "team_leader_email" => Auth::user()->email, "role" => "Main Player/Member " . ($i + 1), "event_name" => $event->name, "event_kicker" => $event->kicker]));
            }

            // Find the User ID of reseve team members
            for ($i = 0; $i < $event->team_members_reserve; $i++){
                if (!$request->has("team_member_reserve_" . ($i + 1)) || $request->input("team_member_reserve_" . ($i + 1)) == "") continue;
                $tempdetails = json_decode(json_encode(DB::table("users")->where("email", $request->input("team_member_reserve_" .($i + 1)))->first()), true);
                for ($j = 0; $j < $slots; $j++){
                    if ($request->has("team_member_reserve_" . ($i + 1))){
                        $temp = $draft;
                        $temp["ticket_id"] = $tempdetails["id"];
                        $temp["remarks"] = "Team Member (Reserve)";
                    }
                    if ($slots > 1) $temp["remarks"] = $temp["remarks"] . ", Slot " . ($j + 1);
                    array_push($query, $temp);
                }
                // Send Email
                Mail::to($request->input("team_member_reserve_" . ($i + 1)))->send(new SendNewTeamNotification(["name" => $tempdetails["name"], "team_name" => $request->input("team_name"), "team_id" => $team_id, "team_leader_name" => Auth::user()->name, "team_leader_email" => Auth::user()->email, "role" => "Reserve Player/Member " . ($i + 1), "event_name" => $event->name, "event_kicker" => $event->kicker]));
            }
            // Insert into the database
            DB::table("registration")->insert($query);
        } else {
            // Assign the participant
            DB::table("registration")->insert(["ticket_id" => Auth::user()->id, "event_id" => $event_id, "status" => 0, "payment_code" => $payment_code]);
        }

        // Send Email for Payment
        if($event->price > 0) Mail::to(Auth::user()->email)->send(SendInvoice::createEmail((object) ["name" => Auth::user()->name, "event_name" => $event->name, "payment_code" => $payment_code, "total_price" => $event->price * $slots]));

        // Return Response
        return redirect('/events/' . $event_id);
    }
}
