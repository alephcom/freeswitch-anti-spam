<?php

namespace App\Http\Controllers;

use App\Models\Clid;
use App\Utilities;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class CallController extends Controller
{

    private int $max_attempts;
    private int $banned_days;
    private int $track_attempts_days;
    private int $whitelisted_days;
    private string $override;
    private bool $hangup;

    private \Illuminate\Redis\Connections\Connection $redis;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->redis = Redis::connection();
        $this->override = config('app.override');
        $this->max_attempts = config('app.max_attempts');
        $this->banned_days = config('app.banned_days');
        $this->track_attempts_days = config('app.track_attempts_days');
        $this->whitelisted_days = config('app.whitelisted_days');
//        $this->middleware('auth');
    }

    public function call(Request $request)
    {
        $this->hangup = false;

        if ($request->has('exiting') && $request->input('exiting') == "true") {
            $xml = new \XMLWriter();
            $xml->openMemory();
            $xml->setIndent(1);
            $xml->startDocument();
            header('Content-Type: text/xml');
            $xml->startElement('document');
            $xml->writeAttribute('type', 'xml/freeswitch-httapi');
            $xml->startElement('variables');
            //$xml->startElement('test');
            //$xml->text("testvariable");
            //$xml->endElement(); //test
            $xml->endElement(); //variables

            $xml->endElement(); //document
            $response = $xml->flush();
            Log::info('returned exiting response');
            return response($response, 200)->header('Content-Type', 'text/xml');
        }

        Log::info(json_encode($request->input(), JSON_PRETTY_PRINT));
        $dest = $request->input('Caller-Destination-Number');
        $source = $request->input("Caller-Caller-ID-Number");

        $clid = Utilities::sanitize($source);

        $config = Cache::get('did_config_' . $dest);

        $type = "daily_pin";

        if ($config) {
            $type = $config['type'];
        }

        if ($type === "daily_pin") {
            $response = $this->typeDailyPin($request, $dest,$source,$clid,$config);
        } elseif ($type === "daytime") {
            $response = $this->typeDaytime($request, $dest,$source,$clid,$config);
        } elseif ($type === "callerid") {
            $response = $this->typeCallerID($request, $dest,$source,$clid,$config);
        }

        Log::info($response);
        return response($response, 200)->header('Content-Type', 'text/xml');
    }


    public function callStats(Request $request) {
        if (!$request->input('token') === config('app.token')) {
            return response('Not Available', 401);
        }
        $clids = Clid::get();
        return $clids;
    }

    public function clidPurge(Request $request, $clid) {
        if (!$request->input('token') === config('app.token')) {
            return response('Not Available', 401);
        }
        $clid = $this->sanitize($clid);
        Cache::delete(
            'blacklisted_clid_' . $clid
        );
        Cache::delete(
            'attempts_clid_' . $clid
        );
        Cache::delete(
            'whitelisted_clid_' . $clid
        );
        return response('Clid Removed', 200);
    }

    private function typeCallerid(Request $request, $dest, $source,$clid,$config)
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(1);
        $xml->startDocument();
        header('Content-Type: text/xml');
        $xml->startElement('document');
        $xml->writeAttribute('type', 'xml/freeswitch-httapi');

        $xml->startElement('variables');
        //$xml->startElement('test');
        //$xml->text("testvariable");
        //$xml->endElement();
        $xml->endElement();

        $xml->startElement('work');
        $allowed = false;
        foreach ($config['allowed_rule']['starts_with'] AS $value) {
            if (Str::of($clid)->startsWith($value)) {
                $allowed = true;
            }
        }
        if ($allowed) {
            if (strlen($config['pass_recording_text']) > 0 ) {
                $xml->startElement('playback');
                //$xml->writeAttribute('name', "banned");
                $xml->writeAttribute('file',
                    url("storage/" . md5($config['did'] . $config['pass_recording_text']) . ".mp3"));
                $xml->endElement();
            }
            $xml->startElement('break');
            $xml->endElement();
        } else {
            if (strlen($config['fail_recording_text']) > 0 ) {
                $xml->startElement('playback');
                //$xml->writeAttribute('name', "banned");
                $xml->writeAttribute('file',
                    url("storage/" . md5($config['did'] . $config['fail_recording_text']) . ".mp3"));
                $xml->endElement();
            }
            $xml->startElement('hangup');
            $xml->writeAttribute('cause', 'USER_BUSY');
            $xml->endElement();
        }
        $xml->endElement(); // </work>
        $xml->endElement(); // </document>
        return $xml->flush();
    }

    private function typeDaytime(Request $request, $dest, $source,$clid,$config) {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(1);
        $xml->startDocument();
        header('Content-Type: text/xml');
        $xml->startElement('document');
        $xml->writeAttribute('type', 'xml/freeswitch-httapi');

        $xml->startElement('variables');
        //$xml->startElement('test');
        //$xml->text("testvariable");
        //$xml->endElement();
        $xml->endElement();

        $xml->startElement('work');

        $current_day_of_week = Str::lower(Carbon::now()->shortEnglishDayOfWeek);
        $allowed = false;

        // First check day of week.
        if (in_array($current_day_of_week, $config['allowed_rule']['days'])) {
            // Next check hours.
            $current_hour = Carbon::now($config['allowed_rule']['timezone'])->hour;
            if ($current_hour >= $config['allowed_rule']['start_hour'] &&
                $current_hour <= $config['allowed_rule']['end_hour']) {
                $allowed = true;
            }
        }
        if ($allowed) {
            if (strlen($config['pass_recording_text']) > 0 ) {
                $xml->startElement('playback');
                //$xml->writeAttribute('name', "banned");
                $xml->writeAttribute('file',
                    url("storage/" . md5($config['did'] . $config['pass_recording_text']) . ".mp3"));
                $xml->endElement();
            }
            $xml->startElement('break');
            $xml->endElement();
        } else {
            if (strlen($config['fail_recording_text']) > 0 ) {
                $xml->startElement('playback');
                //$xml->writeAttribute('name', "banned");
                $xml->writeAttribute('file',
                    url("storage/" . md5($config['did'] . $config['fail_recording_text']) . ".mp3"));
                $xml->endElement();
            }
            $xml->startElement('hangup');
            $xml->writeAttribute('cause', 'USER_BUSY');
            $xml->endElement();
        }
        $xml->endElement(); // </work>
        $xml->endElement(); // </document>
        return $xml->flush();
    }

    private function typeDailyPin($request, $dest,$source,$clid, $config) {
        $pin = Carbon::now()->dayOfWeek;

        $attempts = Cache::get('attempts_clid_' . $clid, 0);

        Log::info('PIN: ' . $request->input('pin'));
        Log::info('Source:  ' . $source);
        Log::info('Dest: ' . $dest);
        Log::info('Attempts: ' . $attempts);

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(1);
        $xml->startDocument();
        header('Content-Type: text/xml');
        $xml->startElement('document');
        $xml->writeAttribute('type', 'xml/freeswitch-httapi');

        $xml->startElement('variables');
        //$xml->startElement('test');
        //$xml->text("testvariable");
        //$xml->endElement();
        $xml->endElement();

        $xml->startElement('work');

        Cache::put(
            'attempts_clid_' . $clid,
            $attempts + 1,
            Carbon::now()->addDays($this->track_attempts_days)->toDateTimeString()
        );

        if ($attempts >= $this->max_attempts) {
            Utilities::blacklist($clid, $this->banned_days);
            $this->hangup = true;
        }
        if ($request->has('override') && $request->input('override') === $this->override) {
            Utilities::whitelist($clid, $this->whitelisted_days);
        } elseif ($request->has('override') && $attempts >= $this->max_attempts) {
            // If they've failed to work through the blacklist the second time they get blacklisted for twice as long.
            Utilities::blacklist($clid, $this->banned_days * 2);
            $this->hangup = true;
        }

        if (Cache::get('whitelisted_clid_' . $clid, false)) {
            // whitelisted
            $xml->startElement('break');
            $xml->endElement();
        } else if ($attempts >= $this->max_attempts + 2 || $this->hangup === true) {
            $xml->startElement('pause');
            $xml->writeAttribute('milliseconds', "2000");
            $xml->endElement();

            $xml->startElement('playback');
            $xml->writeAttribute('name', "banned");
            $xml->writeAttribute('file', url("storage/" . md5("config_temporarily_banned") . ".mp3"));
            $xml->endElement();

            $xml->startElement('hangup');
            $xml->writeAttribute('cause', 'USER_BUSY');
            $xml->endElement();
        } else if (Cache::get('blacklisted_clid_' . $clid, false)) {
            $xml->startElement('pause');
            $xml->writeAttribute('milliseconds', "2000");
            $xml->endElement();

            $xml->startElement('playback');
            $xml->writeAttribute('name', "override");
            $xml->writeAttribute('file', url("storage/" . md5("config_temporarily_banned_override") . ".mp3"));
            //$xml->writeAttribute('error-file', url("audio/did_not_receive_response.mp3"));
            $xml->writeAttribute('digit-timeout', "4000");
            $xml->writeAttribute('input-timeout', "10000");
            //$xml->startElement("bind");
            $xml->writeAttribute('strip',"#");
            $xml->text("~\d\d\d\d\d#");
            $xml->endElement();

        } else if (strval($pin) === $request->input('pin')) {
            // pin matches
            Utilities::whitelist($clid, $this->whitelisted_days);
            $xml->startElement('break');
            $xml->endElement();
        } else if ($request->has('pin')) {
            // Request has pin but does not match.
            $xml->startElement('playback');
            $xml->writeAttribute('name', "pin");
            $xml->writeAttribute('file', url("storage/" . md5("config_press_" . $pin) . ".mp3"));
            $xml->writeAttribute('error-file', url("storage/" . md5("config_did_not_receive_response") . ".mp3"));
            $xml->writeAttribute('digit-timeout', "1000");
            $xml->writeAttribute('input-timeout', "5000");
            $xml->startElement("bind");
            $xml->text("~\d");
            $xml->endElement();
        } else {
            // First time around.
            $xml->startElement('pause');
            $xml->writeAttribute('milliseconds', "2000");
            $xml->endElement();

            $xml->startElement('playback');
            $xml->writeAttribute('file', url("storage/" . md5("config_default_first") . ".mp3"));
            $xml->endElement();

            $xml->startElement('playback');
            $xml->writeAttribute('name', "pin");
            $xml->writeAttribute('file', url("storage/" . md5("config_press_" . $pin) . ".mp3"));
            $xml->writeAttribute('error-file', url("storage/" . md5("config_did_not_receive_response") . ".mp3"));
            $xml->writeAttribute('digit-timeout', "1000");
            $xml->writeAttribute('input-timeout', "5000");
            $xml->startElement("bind");
            $xml->text("~\d");
            $xml->endElement();
        }

        $xml->endElement(); // </work>
        $xml->endElement(); // </document>
        return $xml->flush();
    }

}
