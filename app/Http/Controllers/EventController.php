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
    public function encrypt($data, $password)
    {
        $iv = substr(sha1(mt_rand()), 0, 16);
        $password = sha1($password);

        $salt = sha1(mt_rand());
        $saltWithPassword = hash('sha256', $password . $salt);

        $encrypted = openssl_encrypt(
            "$data",
            'aes-256-cbc',
            "$saltWithPassword",
            0,
            $iv
        );
        $msg_encrypted_bundle = "$iv:$salt:$encrypted";
        return $msg_encrypted_bundle;
    }


    public function decrypt($msg_encrypted_bundle, $password)
    {
        $password = sha1($password);

        $components = explode(':', $msg_encrypted_bundle);
        $iv            = $components[0];
        $salt          = hash('sha256', $password . $components[1]);
        $encrypted_msg = $components[2];

        $decrypted_msg = openssl_decrypt(
            $encrypted_msg,
            'aes-256-cbc',
            $salt,
            0,
            $iv
        );

        if ($decrypted_msg === false)
            return false;
        return $decrypted_msg;
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
        if (!parent::requiresLogin($path, 0, true, false)) return redirect('home');

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
        if (!$event) {
            Session::put('error', 'The event you requested is not found.');
            return redirect('home');
        }

        // Set these variables to null to avoid querying some unnecessary data
        $user = null;
        $registrations = [];
        $rejected = [];
        $event_permissions = null;
        $user_properties = null;
        $event->late = new DateTime($event->date) < new DateTime(date("Y-m-d H:i:s"));

        // Check whether the user has been logged in and registered
        if (Auth::check()) {
            $user = Auth::user();
            $registrations = DB::table('registration')->where('ticket_id', $user->id)->where('event_id', $event->id)->where('status', '!=', 1)->get();
            $rejected = DB::table('registration')->where('ticket_id', $user->id)->where('event_id', $event->id)->where('status', 1)->get();
        }

        $admin_or_committee = parent::requiresLogin($request->url(), $event->id, true, true);
        Session::remove('error');

        // Check whether the event is private
        if ($event->private && !$admin_or_committee) {
            // Check whether the user has been registered to that event
            if (count($registrations) == 0) {
                Session::put('error', 'The event you requested is not found.');
                return redirect('home');
            }
        }

        $validation = (object) [
            'event_permissions' => [],
            'eligible_to_register' => false
        ];

        // If the registration is opened
        if ($event->opened) {
            // Check permissions and validation
            // Load from cache
            $event_permissions = Cache::get('event_permissions_' . $event->id, []);
            if (count($event_permissions) == 0) {
                $event_permissions = DB::table('event_permissions')->join('fields', 'event_permissions.field_id', 'fields.id')->where('event_id', $event->id)->get();
                Cache::put('event_permissions_' . $event->id, $event_permissions, 300);
            }

            if ($user) {
                $user_properties = DB::table('user_properties')->where('user_id', $user->id)->get();
                $validation = parent::validateFields($event_permissions, $user_properties);
            }
        }
        if ($event->slots - count($registrations) <= 0) $validation->eligible_to_register = false;
        // Return view
        return view('event-details', ['event' => $event, 'user' => $user, 'registrations' => $registrations, 'rejected' => $rejected, 'admin_or_committee' => $admin_or_committee, 'event_permissions' => $validation->event_permissions, 'eligible_to_register' => $validation->eligible_to_register]);
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
        if (!Auth::check()) {
            $request->session()->put('error', 'Please log in to continue.');
            return redirect('home');
        }

        // Check whether the event exists
        $event = DB::table('events')->where('id', $id)->first();
        if (!$event) {
            $request->session()->put('error', 'This event does not exist.');
            return redirect('home');
        }

        // Check whether the user is an admin or committee
        $check = parent::checkAdminOrCommittee(Auth::id(), $id);
        if (!$check->admin && !$check->committee) {
            // If not simply return to main page
            return redirect('events/' . $id);
        }

        // Gather the data
        $data = DB::table('registration')->select('registration.*', 'users.name', 'users.email', 'users.verified', 'users.email_verified_at', 'users.university_id', 'users.created_at', 'users.updated_at')->join('users', 'users.id', 'registration.ticket_id')->where('event_id', $id)->get();

        $event->current_seats = count($data);
        $event->attending = 0;
        $event->attended = 0;
        $event->offline_attandance = 0;
        $event->online_attandance = 0;
        foreach ($data as $registration) {
            if ($registration->status == 1) $event->current_seats--;
            else {
                if ($registration->offline_status == 0) $event->online_attandance++;
                else $event->offline_attandance++;
            }
            if ($registration->status == 4) $event->attending++;
            if ($registration->status == 5) $event->attended++;
        }
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
        if (!parent::requiresLogin($request->path(), $id, true, false)) return redirect('home');

        $force_change = false;
        if ($request->has('flag-force-change') && $request->input('flag-force-change') == "checked") $force_change = true;

        foreach ($request->all() as $key => $value) {
            if (Str::startsWith($key, "status-offline-") && $value >= 0) {
                $key = substr($key, 15);
                DB::table('registration')->where('id', $key)->update(['offline_status' => $value]);
            } else if (Str::startsWith($key, "status-") && $value >= 0) {
                $key = substr($key, 7);
                DB::table('registration')->where('id', $key)->update(['status' => $value]);
            } else if (Str::startsWith($key, "action-")) switch ($key) {
                case "action-update-kicker":
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['kicker' => $value]);
                    break;
                case "action-update-name":
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['name' => $value]);
                    break;
                case "action-update-date":
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['date' => new DateTime($value)]);
                    break;
                case "action-update-location":
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['location' => $value]);
                    break;
                case "action-update-price":
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['price' => $value]);
                    break;
                case "action-update-offline_price":
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['offline_price' => $value]);
                    break;
                case "action-update-cover_image":
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['cover_image' => $value]);
                    break;
                case "action-update-theme_color_foreground":
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['theme_color_foreground' => $value]);
                    break;
                case "action-update-theme_color_background":
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['theme_color_background' => $value]);
                    break;
                case "action-update-description_public":
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['description_public' => $value]);
                    break;
                case "action-update-description_pending":
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['description_pending' => $value]);
                    break;
                case "action-update-description_private":
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['description_private' => $value]);
                    break;
                case "action-registration-status":
                    if ($value == "enabled") DB::table('events')->where('id', $id)->update(['opened' => 1]);
                    else if ($value == "disabled") DB::table('events')->where('id', $id)->update(['opened' => 0]);
                    break;
                case "action-registration-private":
                    if ($value == "private") DB::table('events')->where('id', $id)->update(['private' => 1]);
                    else if ($value == "public") DB::table('events')->where('id', $id)->update(['private' => 0]);
                    break;
                case "action-registration-auto_accept":
                    if ($value == "enabled") DB::table('events')->where('id', $id)->update(['auto_accept' => 1]);
                    else if ($value == "disabled") DB::table('events')->where('id', $id)->update(['auto_accept' => 0]);
                    break;
                case "action-registration-offline_auto_accept":
                    if ($value == "enabled") DB::table('events')->where('id', $id)->update(['offline_auto_accept' => 1]);
                    else if ($value == "disabled") DB::table('events')->where('id', $id)->update(['offline_auto_accept' => 0]);
                    break;
                case "action-registration-event_offline_status":
                    if ($value == "enabled") DB::table('events')->where('id', $id)->update(['event_offline_status' => 1]);
                    else if ($value == "disabled") DB::table('events')->where('id', $id)->update(['event_offline_status' => 0]);
                    break;
                case "action-update-seats":
                    if ($value > 0) DB::table('events')->where('id', $id)->update(['seats' => $value]);
                    break;
                case "action-update-offline_seats":
                    if ($value >= 0) DB::table('events')->where('id', $id)->update(['offline_seats' => $value]);
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
                case "action-update-payment_link":
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['payment_link' => $value]);
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
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['url_link' => $value]);
                    break;
                case "action-update-totp_key":
                    if ($force_change || $value != '') DB::table('events')->where('id', $id)->update(['totp_key' => $value]);
                    break;
                case "action-update-PassEvent":
                    if ($value == "enabled") DB::table('events')->where('id', $id)->update(['PassEvent' => 1]);
                    else if ($value == "disabled") DB::table('events')->where('id', $id)->update(['PassEvent' => 0]);
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
        if (!$event->attendance_opened && $event->attendance_is_exit) return response('Attendance period has been closed', 403);

        // Check attendance type
        $is_exit = $event->attendance_is_exit;
        $timestamp = Carbon::now();
        $event->late = new DateTime($event->date) < new DateTime(date("Y-m-d H:i:s"));

        if ($is_exit) {
            // Get the exit token
            $token = $request->input('token');
            if (strlen($token) == 0 || $token != '' . $event->totp_key) return response('Incorrect token', 401);

            if (strlen($registration->check_in_timestamp) > 0) {
                // Record exit attendance
                DB::table('registration')->where('id', $id)->update(['check_out_timestamp' => $timestamp, 'status' => 5]);
            } else {
                // Record new attendance
                DB::table('registration')->where('id', $id)->update(['check_out_timestamp' => $timestamp, 'status' => 4, 'remarks' => 'Late']);
            }
        } else if (strlen($registration->check_in_timestamp . $registration->check_out_timestamp) == 0) {
            if ($event->attendance_opened) {
                // Record new attendance
                DB::table('registration')->where('id', $id)->update(['check_in_timestamp' => $timestamp, 'status' => 4, 'remarks' => 'On Time']);
            } else if ($event->late) {
                // Record new attendance
                DB::table('registration')->where('id', $id)->update(['check_in_timestamp' => $timestamp, 'status' => 4, 'remarks' => 'Late']);
            }
        }
        // return response()->json([
        //     'timestamp' => $timestamp,
        //     'attendanceType' => ($is_exit ? 'exit' : 'entrance'),
        //     'url' => $event->url_link
        // ])->setStatusCode(503);
        return response()->json([
            'timestamp' => $timestamp,
            'attendanceType' => ($is_exit ? 'exit' : 'entrance'),
            'url' => $event->url_link
        ]);
    }

    // Module to get user details
    public function getUserDetails(Request $request)
    {
        // Ensure that the user has logged in
        if (!Auth::check()) return response()->json(['error' => 'You are not authenticated']);
        // Ensure that the user has complete payload
        if (!$request->has('email') || !$request->has('eventId')) return response()->json(['error' => 'Incomplete Request']);
        $user = null;
        if ($request->input('email') == Auth::user()->email) {
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
        if (count($event_permissions) == 0) {
            $event_permissions = DB::table('event_permissions')->join('fields', 'event_permissions.field_id', 'fields.id')->where('event_id', $event->id)->get();
            Cache::put('event_permissions_' . $event->id, $event_permissions, 300);
        }

        // Check permissions and validation
        $user_properties = DB::table('user_properties')->where('user_id', $user->id)->get();
        $validation = parent::validateFields($event_permissions, $user_properties);
        $registrations = DB::table('registration')->where('ticket_id', $user->id)->where('event_id', $request->get('eventId'))->where('status', '!=', 1)->get();

        if ($event->slots - count($registrations) <= 0) $validation->eligible_to_register = false;

        return response()->json([
            'name' => $user->name,
            'eligibleToRegister' => $validation->eligible_to_register,
            'remainingSlots' => $event->slots - count($registrations),
            'eventPermissions' => $validation->event_permissions
        ]);
    }

    // Module to register to certain events
    public function registerToEvent(Request $request)
    {
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
        $currentTickets = DB::table('registration')->selectRaw('count(*) as total')->where('event_id', $event->id)->where('status', '!=', 1)->first();

        if (!$event) {
            $request->session()->put('error', "Event not found.");
            return redirect('/home');
        } else if ($currentTickets->total >= $event->seats) {
            $request->session()->put('error', "Unable to register to " . $event->name . " due to full capacity.");
            return redirect('/home');
        } else if ($event->opened == 0) {
            $request->session()->put('error', "The registration period for " . $event->name . " has been closed.");
            return redirect('/home');
        } else if ($event->team_members + $event->team_members_reserve > 0) $team_required = true;

        if ($event->price > 0 || $event->offline_price > 0)     $payment_code = uniqid();
        $is_Offline = 0;
        if ($event->event_offline_status == 1 && !$request->has("OnlineOfflineStatus")) {
            return back()->with('OfflineInput', 'True');
        } else if ($event->event_offline_status == 0) {
            $is_Offline = 0;
        } else if ($event->event_offline_status == 2) {
            $is_Offline = 1;
        } else {
            $is_Offline  = $request->OnlineOfflineStatus;
        }
        // Create an array of users to be validated
        $leader = Auth::user();
        $members = [];
        $reserve = [];

        // Create an email draft
        $event_title = (strlen($event->kicker) > 0 ? ($event->kicker . ': ') : '') . $event->name;
        $email_template = [
            'message_type' => 'MARKDOWN',
            'sender_name' => 'HIMTI - ' . (strlen($event->kicker) > 0 ? $event->kicker : $event->name),
            'created_at' => date("Y-m-d H:i:s")
        ];

        // Get whether teams are needed
        if ($team_required == true) {
            if (!$request->has("create_team") || !$request->has("team_name") || $request->input("team_name") == "") {
                $request->session()->put('error', "You will need to create a team for " . $event->name . ".");
                return redirect('/home');
            }

            // Team members
            for ($i = 0; $i < $event->team_members; $i++) {
                if (!$request->has('team_member_' . $i)) {
                    $request->session()->put('error', "Incomplete team members");
                    return redirect('/home');
                }
                array_push($members, DB::table('users')->where('email', 'team_member_' . $i));
            }

            // Reserve members
            for ($i = 0; $i < $event->team_members; $i++) {
                if ($request->has('team_member_reserve_' . $i)) array_push($reserve, DB::table('users')->where('email', 'team_member_reserve_' . $i));
            }
        }

        // Validate users
        $queue = [$leader];
        $queue = array_merge($queue, $members, $reserve);
        $event_permissions = DB::table('event_permissions')->where('event_id', $event->id)->get();

        $validation_failed = 0;

        foreach ($queue as $user) {
            $user_properties = DB::table('user_properties')->where('user_id', $user->id)->get();
            $registrations = DB::table('registration')->where('event_id', $event->id)->where('ticket_id', $user->id)->where('status', '!=', 1)->get();
            $validation = parent::validateFields($event_permissions, $user_properties);
            if ($event->slots - count($registrations) <= 0) $validation->eligible_to_register = false;

            if (!$validation->eligible_to_register) {
                $validation_failed++;
            }
        }

        if ($validation_failed > 0) {
            $request->session()->put('error', "You or your team members are not eligible to register to this event.");
            return redirect('/home');
        }

        if ($team_required == true) {
            // Create a new team
            $team_id = DB::table("teams")->insertGetId(["name" => $request->input("team_name"), "event_id" => $event_id]);

            // Assign the database template
            $query = [];
            if ($is_Offline == 0) {
                $draft = ["event_id" => $event_id, "status" => (($event->auto_accept == true) ? 2 : 0), "payment_code" => $payment_code, "team_id" => $team_id, "ticket_id" => null, "remarks" => null];
            } else {
                $draft = ["event_id" => $event_id, "status" => (($event->offline_auto_accept == true) ? 2 : 0), "payment_code" => $payment_code, "team_id" => $team_id, "ticket_id" => null, "remarks" => null];
            }


            // Assign the User ID of the team leader
            $tempdetails = json_decode(json_encode(Auth::user()), true);
            for ($j = 0; $j < $slots; $j++) {
                $temp = $draft;
                $temp["ticket_id"] = $tempdetails["id"];
                $temp["remarks"] = "Team Leader";
                if ($slots > 1) $temp["remarks"] = $temp["remarks"] . ", Slot " . ($j + 1);
                array_push($query, $temp);
            }

            // Find the User ID of team members
            for ($i = 0; $i < $event->team_members; $i++) {
                $tempdetails = json_decode(json_encode(DB::table("users")->where("email", $request->input("team_member_" . ($i + 1)))->first()), true);
                for ($j = 0; $j < $slots; $j++) {
                    $temp = $draft;
                    echo (print_r($tempdetails));
                    $temp["ticket_id"] = $tempdetails["id"];
                    $temp["remarks"] = "Team Member";
                    if ($slots > 1) $temp["remarks"] = $temp["remarks"] . ", Slot " . ($j + 1);
                    array_push($query, $temp);
                }
                // Send Email
                // Mail::to($request->input("team_member_" . ($i + 1)))->send(new SendNewTeamNotification(["name" => $tempdetails["name"], "team_name" => $request->input("team_name"), "team_id" => $team_id, "team_leader_name" => Auth::user()->name, "team_leader_email" => Auth::user()->email, "role" => "Main Player/Member " . ($i + 1), "event_name" => $event->name, "event_kicker" => $event->kicker]));
                $email_draft = $email_template;
                $email_draft['subject'] = 'You have been invited to join ' . $event_title . ' by ' . $leader->name;
                $email_draft['message'] = 'You have been invited by ' . $leader->name . ' (' . $leader->email . ') to join as a member of "' . $request->input("team_name") . '" to join ' . $event_title . PHP_EOL . PHP_EOL . 'Your team and ticket details can be found on https://registration.himti.or.id/events/' . $event->id . '/.' . PHP_EOL . PHP_EOL . 'If you are being added by mistake, please contact the respective event committees.';
                if ($event->price == 0 && $is_Offline == 0 && $event->auto_accept == true && strlen($event->description_private) > 0) $email_template['message'] .= PHP_EOL . PHP_EOL . '## Important Information for Event/Attendance' . PHP_EOL . PHP_EOL . $event->description_private;
                if ($event->price == 0 && $is_Offline == 1 && $event->offline_auto_accept == true && strlen($event->description_private) > 0) $email_template['message'] .= PHP_EOL . PHP_EOL . '## Important Information for Event/Attendance' . PHP_EOL . PHP_EOL . $event->description_private;
                else if (strlen($event->description_pending) > 0) $email_template['message'] .= PHP_EOL . PHP_EOL . '## Important Information for Event/Attendance' . PHP_EOL . PHP_EOL . $event->description_pending;
                $email_draft['email'] = $tempdetails->email;
                DB::table('email_queue')->insert($email_draft);
            }

            // Find the User ID of reseve team members
            for ($i = 0; $i < $event->team_members_reserve; $i++) {
                if (!$request->has("team_member_reserve_" . ($i + 1)) || $request->input("team_member_reserve_" . ($i + 1)) == "") continue;
                $tempdetails = json_decode(json_encode(DB::table("users")->where("email", $request->input("team_member_reserve_" . ($i + 1)))->first()), true);
                for ($j = 0; $j < $slots; $j++) {
                    if ($request->has("team_member_reserve_" . ($i + 1))) {
                        $temp = $draft;
                        $temp["ticket_id"] = $tempdetails["id"];
                        $temp["remarks"] = "Team Member (Reserve)";
                    }
                    if ($slots > 1) $temp["remarks"] = $temp["remarks"] . ", Slot " . ($j + 1);
                    array_push($query, $temp);
                }
                // Send Email
                // Mail::to($request->input("team_member_reserve_" . ($i + 1)))->send(new SendNewTeamNotification(["name" => $tempdetails["name"], "team_name" => $request->input("team_name"), "team_id" => $team_id, "team_leader_name" => Auth::user()->name, "team_leader_email" => Auth::user()->email, "role" => "Reserve Player/Member " . ($i + 1), "event_name" => $event->name, "event_kicker" => $event->kicker]));
                $email_draft = $email_template;
                $email_draft['subject'] = 'You have been invited to join ' . $event_title . ' by ' . $leader->name;
                $email_draft['message'] = 'You have been invited by ' . $leader->name . ' (' . $leader->email . ') to join as a reserve member of "' . $request->input("team_name") . '" to join ' . $event_title . PHP_EOL . PHP_EOL . 'Your team and ticket details can be found on https://registration.himti.or.id/events/' . $event->id . '/.' . PHP_EOL . PHP_EOL . 'If you are being added by mistake, please contact the respective event committees.';
                if ($event->price == 0 && $is_Offline == 0 &&  $event->auto_accept == true && strlen($event->description_private) > 0) $email_template['message'] .= PHP_EOL . PHP_EOL . '## Important Information for Event/Attendance' . PHP_EOL . PHP_EOL . $event->description_private;
                if ($event->price == 0 && $is_Offline == 1 && $event->offline_auto_accept == true && strlen($event->description_private) > 0) $email_template['message'] .= PHP_EOL . PHP_EOL . '## Important Information for Event/Attendance' . PHP_EOL . PHP_EOL . $event->description_private;
                else if (strlen($event->description_pending) > 0) $email_template['message'] .= PHP_EOL . PHP_EOL . '## Important Information for Event/Attendance' . PHP_EOL . PHP_EOL . $event->description_pending;
                $email_draft['email'] = $tempdetails->email;
                DB::table('email_queue')->insert($email_draft);
            }
            // Insert into the database
            DB::table("registration")->insert($query);
        } else {
            // Assign the participant
            if ($is_Offline == 0) {
                DB::table("registration")->insert(["ticket_id" => Auth::user()->id, "event_id" => $event_id, "status" => (($event->auto_accept == true) ? 2 : 0), "payment_code" => $payment_code, "offline_status" => $is_Offline]);
            } else {
                DB::table("registration")->insert(["ticket_id" => Auth::user()->id, "event_id" => $event_id, "status" => (($event->offline_auto_accept == true) ? 2 : 0), "payment_code" => $payment_code, "offline_status" => $is_Offline]);
            }
        }

        // Send Email for Payment
        // if($event->price > 0) Mail::to(Auth::user()->email)->send(SendInvoice::createEmail((object) ["name" => Auth::user()->name, "event_id" => $event->id, "user_id" => Auth::user()->id, "event_name" => $event->name, "payment_code" => $payment_code, "total_price" => $event->price * $slots]));

        // Send Email for Payment
        $email_template['subject'] = 'Welcome to ' . $event_title . '!';
        $email_template['message'] = 'Thank you for registering to ' . $event_title . '.';
        $email_template['email'] = $leader->email;

        if ($event->price == 0 && $is_Offline == 0 && $event->auto_accept == true) {
            $email_template['message'] .= ' Your registration has been approved by our team.' . PHP_EOL . PHP_EOL . 'Your ticket and team (if any) details can be found on https://registration.himti.or.id/events/' . $event->id . '/.' . PHP_EOL . PHP_EOL . 'If you are being registered by mistake, please contact the respective event committees.';
            if (strlen($event->description_private) > 0) $email_template['message'] .= PHP_EOL . PHP_EOL . '## Important Information for Event/Attendance' . PHP_EOL . PHP_EOL . $event->description_private;
        } else if ($event->offline_price == 0 && $is_Offline == 1 && $event->offline_auto_accept == true) {
            $d = $this->encrypt($user->id . '~' . $event_id . '~' . date("Y-m-d", strtotime($event->date)), env('Encrypt_Key'));
            // Don't Forget to insert new key Encrypt_Key for the encrypting token 
            $email_template['message'] .= ' Your registration has been approved by our team.' . PHP_EOL . PHP_EOL . 'Your ticket and team (if any) details can be found on https://registration.himti.or.id/events/' . $event->id . '/.' . PHP_EOL . PHP_EOL . 'If you are being registered by mistake, please contact the respective event committees.';
            $email_template['message'] .= PHP_EOL . PHP_EOL . 'Your QR Attendance' . PHP_EOL . PHP_EOL . '![Participant QR](https://chart.googleapis.com/chart?cht=qr&chl=|' . $d . '|&choe=UTF-8&chs=250x250)' . PHP_EOL . PHP_EOL;
            if (strlen($event->description_private) > 0) $email_template['message'] .= PHP_EOL . PHP_EOL . '## Important Information for Event/Attendance' . PHP_EOL . PHP_EOL . $event->description_private;
        } else {
            $email_template['message'] .= ' Please finish your payment (if any) and wait while our team verifies and approves your registration.' . PHP_EOL . PHP_EOL . 'You may check your ticket status regularly on https://registration.himti.or.id/events/' . $event->id . '/.' . PHP_EOL . PHP_EOL . 'If you are being registered by mistake, please contact the respective event committees.';
            if (strlen($event->description_pending) > 0) $email_template['message'] .= PHP_EOL . PHP_EOL . '## Important Information for Event/Attendance' . PHP_EOL . PHP_EOL . $event->description_pending;
        }

        DB::table('email_queue')->insert($email_template);

        // Return Response
        if (($event->price > 0 && $is_Offline == 0) || ($event->offline_price > 0 && $is_Offline == 1)) {
            if (strlen($event->payment_link) > 0) return redirect($this->getPaymentLink($event, (object) ['payment_code' => $payment_code]));
            else return redirect('/pay/' . $payment_code);
        }
        if (strlen($event->payment_link) > 0) return redirect($this->getPaymentLink($event, (object) ['payment_code' => $payment_code]));
        return redirect('/events/' . $event_id);
    }

    public static function getPaymentLink($event, $registration)
    {
        $search = array("%NAME", "%EMAIL", "%PAYMENT_CODE", "%EVENT_ID");
        $replace = array(Auth::user()->name, Auth::user()->email, $registration->payment_code, $event->id);
        $LinkEdited = str_replace($search, $replace, $event->payment_link);
        $registrationsfields = DB::select("SELECT field_id FROM event_permissions WHERE event_id = '" . $event->id . "'");
        $UserDetails = DB::select("SELECT * FROM user_properties WHERE user_id = '" . Auth::id() . "'");

        foreach ($registrationsfields as $registration) {
            $NullData = 0;
            foreach ($UserDetails as $UserDetail) {
                if ($registration->field_id == $UserDetail->field_id) {
                    $LinkEdited = str_replace("%" . $registration->field_id, $UserDetail->value, $LinkEdited);
                    $NullData = 1;
                }
            }
            if ($NullData == 0) {
                $LinkEdited = str_replace("%" . $registration->field_id, "", $LinkEdited);
            }
        }


        return $LinkEdited;
    }

    // Module to register attendance queue
    public static function insertAttendanceQueue(Request $request)
    {
        // Quick validation
        if (!$request->has('clientId') || !$request->has('email') || !$request->has('token')) return response('Incomplete Request', 400);

        // Load from cache
        $attendance_clients = Cache::get('attendance_clients', []);
        if (count($attendance_clients) == 0) {
            $query = DB::table('attendance_clients')->get();
            for ($i = 0; $i < count($query); $i++) $attendance_clients[$query[$i]->id] = $query[$i]->enabled;
            Cache::put('attendance_clients', $attendance_clients, 300);
        }

        $client_id = $request->get('clientId');
        $timestamp = Carbon::now();

        if (!isset($attendance_clients[$client_id]) || $attendance_clients[$client_id] != true) return response('Client not allowed', 403);

        // Validate users
        if (
            (Auth::guest() || Auth::user()->email != $request->get('email')) &&
            !DB::table('users')->where('email', $request->get('email'))->first()
        ) return response('Email not found', 401);

        // Load from cache and validate tokens
        $token_validated = false;

        $event_tokens = Cache::get('event_tokens', []);
        if (count($event_tokens) == 0) {
            $event_tokens = DB::table('events')->where('attendance_opened', true)->where('attendance_is_exit', true)->whereNotNull('totp_key')->get();
            Cache::put('event_tokens', $event_tokens, 300);
        }

        // Make sure that the token matches and the event is opened
        for ($i = 0; $i < count($event_tokens); $i++) {
            if ($event_tokens[$i]->totp_key == $request->get('token')) $token_validated = true;
        }

        if (!$token_validated) return response('Invalid token or the attendance period has been closed', 404);

        // Else...
        $queue_id = DB::table('attendance_queue')->insertGetId([
            'attendance_client_id' => $client_id,
            'email' => $request->get('email'),
            'totp_key' => $request->get('token'),
            'created_at' => $timestamp
        ]);

        return response()->json([
            'timestamp' => $timestamp,
            'attendance_queue_id' => $queue_id
        ]);
    }
}