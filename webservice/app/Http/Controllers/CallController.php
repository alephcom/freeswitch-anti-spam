<?php

namespace App\Http\Controllers;

use App\Models\Clid;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CallController extends Controller
{

    private int $max_attempts = 3;
    private int $banned_days = 30;
    private int $track_attempts_days = 7;
    private int $whitelisted_days = 30;
    private string $override = "45678";

    private bool $hangup = false;

    private $redis;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->redis = Redis::connection();
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

            $xml->startElement('test');
            $xml->text("testvariable");
            $xml->endElement(); //test

            $xml->endElement(); //variables

            $xml->endElement(); //document
            $response = $xml->flush();
            Log::info('returned exiting response');
            return response($response, 200)->header('Content-Type', 'text/xml');
        }

        $pin = Carbon::now()->dayOfWeek;

        Log::info(json_encode($request->input(), JSON_PRETTY_PRINT));
        $dest = $request->input('Caller-Destination-Number');
        $source = $request->input("Caller-Caller-ID-Number");

        $clid = $this->sanitize($source);

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
        $xml->startElement('test');
        $xml->text("testvariable");
        $xml->endElement();
        $xml->endElement();

        $xml->startElement('work');

        Cache::put(
            'attempts_clid_' . $clid,
            $attempts + 1,
            Carbon::now()->addDays($this->track_attempts_days)->toDateTimeString()
        );

        if ($attempts >= $this->max_attempts) {
            $this->blacklist($clid, $this->banned_days);
            $this->hangup = true;
        }
        if ($request->has('override') && $request->input('override') === $this->override) {
            $this->whitelist($clid, $this->whitelisted_days);
        } elseif ($request->has('override') && $attempts >= $this->max_attempts) {
            // If they've failed to work through the blacklist the second time they get blacklisted for twice as long.
            $this->blacklist($clid, $this->banned_days * 2);
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
            $xml->writeAttribute('file', url("audio/temporarily_banned.mp3"));

            $xml->startElement('hangup');
            $xml->writeAttribute('cause', 'USER_BUSY');
            $xml->endElement();
        } else if (Cache::get('blacklisted_clid_' . $clid, false)) {
            $xml->startElement('pause');
            $xml->writeAttribute('milliseconds', "2000");
            $xml->endElement();

            $xml->startElement('playback');
            $xml->writeAttribute('name', "override");
            $xml->writeAttribute('file', url("audio/temporarily_banned_override.mp3"));
            //$xml->writeAttribute('error-file', url("audio/did_not_receive_response.mp3"));
            $xml->writeAttribute('digit-timeout', "4000");
            $xml->writeAttribute('input-timeout', "10000");
            //$xml->startElement("bind");
            $xml->writeAttribute('strip',"#");
            $xml->text("~\d\d\d\d\d#");
            $xml->endElement();

        } else if (strval($pin) === $request->input('pin')) {
            // pin matches
            $this->whitelist($clid, $this->whitelisted_days);
            $xml->startElement('break');
            $xml->endElement();
        } else if ($request->has('pin')) {
            // Request has pin but does not match.
            $xml->startElement('playback');
            $xml->writeAttribute('name', "pin");
            $xml->writeAttribute('file', url("audio/press_" . $pin . ".mp3"));
            $xml->writeAttribute('error-file', url("audio/did_not_receive_response.mp3"));
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
            $xml->writeAttribute('file', url("audio/protection.mp3"));
            $xml->endElement();

            $xml->startElement('playback');
            $xml->writeAttribute('name', "pin");
            $xml->writeAttribute('file', url("audio/press_" . $pin . ".mp3"));
            $xml->writeAttribute('error-file', url("audio/did_not_receive_response.mp3"));
            $xml->writeAttribute('digit-timeout', "1000");
            $xml->writeAttribute('input-timeout', "5000");
            $xml->startElement("bind");
            $xml->text("~\d");
            $xml->endElement();
        }

        $xml->endElement(); // </work>
        $xml->endElement(); // </document>
        $response = $xml->flush();
        Log::info($response);
        return response($response, 200)->header('Content-Type', 'text/xml');
    }

    private function sanitize($clid)
    {
        return preg_replace('/[^0-9]/','',$clid);
    }

    private function whitelist($clid, $days) {
        Cache::delete(
            'blacklisted_clid_' . $clid
        );
        Cache::put(
            'attempts_clid_' . $clid,
            0
        );
        Cache::put(
            'whitelisted_clid_' . $clid,
            true,
            Carbon::now()->addDays($days)->toDateTimeString()
        );
        $c = Clid::where('clid', $clid)->first();
        if (!$c) {
            $c = new Clid;
            $c->clid = $clid;
        }
        $c->expires_at = Carbon::now()->addDays($days);
        $c->status = Clid::STATUS_WHITELISTED;
        $c->save();

        Log::channel('action')->info('WHITELISTED: Days: ' . $days . ' CLID: ' . $clid);
    }

    private function blacklist($clid, $days) {
        Cache::put(
            'blacklisted_clid_' . $clid,
            true,
            Carbon::now()->addDays($days)->toDateTimeString()
        );
        $c = Clid::where('clid', $clid)->first();
        if (!$c) {
            $c = new Clid;
            $c->clid = $clid;
        }
        $c->expires_at = Carbon::now()->addDays($days);
        $c->status = Clid::STATUS_BLACKLISTED;
        $c->save();

        Log::channel('action')->info('BANNED: Days: ' . $days . ' CLID: ' . $clid);
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

}
