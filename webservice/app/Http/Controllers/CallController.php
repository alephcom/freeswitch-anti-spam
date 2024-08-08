<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CallController extends Controller
{

    private int $max_attempts = 3;
    private int $banned_days = 30;
    private int $whitelisted_days = 30;
    private string $override = "45678";

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
//        $this->middleware('auth');
    }

    public function call(Request $request)
    {
        $pin = Carbon::now()->dayOfWeek;

        Log::info(json_encode($request->input()));
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

        $xml->startElement('work');

        Cache::put(
            'attempts_clid_' . $clid,
            $attempts + 1,
            Carbon::now()->addDays($this->banned_days)->toDateTimeString()
        );

        if ($attempts >= $this->max_attempts) {
            Cache::put(
                'blacklisted_clid_' . $clid,
                true,
                Carbon::now()->addDays($this->banned_days)->toDateTimeString()
            );
        }
        if ($request->has('override') && $request->input('override') === $this->override) {
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
                Carbon::now()->addDays($this->whitelisted_days)->toDateTimeString()
            );
        } elseif ($request->has('override') && $attempts >= $this->max_attempts) {
            // If they've failed to work through the blacklist the second time they get blacklisted for twice as long.
            Cache::put(
                'blacklisted_clid_' . $clid,
                true,
                Carbon::now()->addDays($this->banned_days * 2)->toDateTimeString()
            );
        }

        if (Cache::get('whitelisted_clid_' . $clid, false)) {
            // whitelisted
            $xml->startElement('break');
            $xml->endElement();
        } else if (Cache::get('blacklisted_clid_' . $clid, false)) {
            $xml->startElement('pause');
            $xml->writeAttribute('milliseconds', "2000");
            $xml->endElement();

            //$xml->startElement('playback');
            //$xml->writeAttribute('name', "pin");
            //$xml->writeAttribute('file', url("audio/temporarily_banned.mp3"));

            $xml->startElement('playback');
            $xml->writeAttribute('name', "override");
            $xml->writeAttribute('file', url("audio/temporarily_banned.mp3"));
            //$xml->writeAttribute('error-file', url("audio/did_not_receive_response.mp3"));
            //$xml->writeAttribute('digit-timeout', "1000");
            //$xml->writeAttribute('input-timeout', "5000");
            //$xml->startElement("bind");
            $xml->writeAttribute('strip',"#");
            $xml->text("~\d\d\d\d\d#");
            $xml->endElement();

            /*$xml->startElement("speak");
            $xml->writeAttribute("engine", "flite");
            $xml->writeAttribute("voice", "kal");
            $xml->text("Your number has been temporarily banned from this system. Goodbye");
            $xml->endElement(); // </pause>*.
//

            /*$xml->startElement('playback');
            $xml->writeAttribute('name', 'digits');
            $xml->writeAttribute('file', 'exten.wav');
            $xml->writeAttribute('error-file', 'http://sidious.freeswitch.org/sounds/invalid.wav');
            $xml->writeAttribute('input-timeout', '5000');
            $xml->writeAttribute('action', 'dial:default:XML');
            $xml->endElement(); // </pause>
            // */

            $xml->startElement('hangup');
            $xml->writeAttribute('cause', 'USER_BUSY');
            $xml->endElement();

//$xml->startElement("bind");
//$xml->writeAttribute('strip',"#");
//$xml->text("~\\d+\#");
//$xml->endElement(); // </bind>
//$xml->endElement(); // </playback>

        } else if (strval($pin) === $request->input('pin')) {
            // pin matches
            Cache::put(
                'attempts_clid_' . $clid,
                0
            );

            Cache::put(
                'whitelisted_clid_' . $clid,
                true,
                Carbon::now()->addDays($this->whitelisted_days)->toDateTimeString()
            );

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
        return $clid;
    }

}
