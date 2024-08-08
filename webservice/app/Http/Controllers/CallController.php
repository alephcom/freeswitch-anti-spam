<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallController extends Controller
{
	/**
     * Create a new controller instance.
     *
     * @return void
     */

	public $pin;

    public function __construct()
    {
//        $this->middleware('auth');
	  $this->pin = "1";
    }

    public function call(Request $request) {
	Log::info(json_encode($request->input()));
	$dest = $request->input('Caller-Destination-Number');
//		Caller-Caller-ID-Name":"LLOYDMINSTER SK",
	$source = $request->input("Caller-Caller-ID-Number");
	Log::info('PIN: ' . $request->input('pin'));

	$clid = $this->sanitize($source);
	$xml = new \XMLWriter();
$xml->openMemory();
$xml->setIndent(1);
$xml->startDocument();
header('Content-Type: text/xml');
$xml->startElement('document');
$xml->writeAttribute('type', 'xml/freeswitch-httapi');

$xml->startElement('work');

	if ($this->blacklisted($clid)) {

    $xml->startElement("speak");
        $xml->writeAttribute("engine","flite");
    $xml->writeAttribute("voice", "kal");
    $xml->text("Your number has been temporarily banned from this system. Goodbye" );
$xml->endElement(); // </pause>
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

        } else if ($this->pin === $request->input('pin')) {
            $xml->startElement('break');
            $xml->endElement();
        } else {

  $xml->startElement('playback');
  $xml->writeAttribute('name', "pin");
  $xml->writeAttribute('file', "ivr/32000/ivr-to_accept_press_one.wav");
  $xml->writeAttribute('error-file', "ivr/32000/ivr-please_reenter_your_pin.wav");
  $xml->writeAttribute('digit-timeout', "1000");
  $xml->writeAttribute('input-timeout', "5000");

  $xml->startElement("bind");
//    $xml->writeAttribute('strip', "#");
//  $xml->text("~\\d+\#");
  $xml->text("~\d");
  $xml->endElement();


	}
        $xml->endElement(); // </work>
        $xml->endElement(); // </document>
        $response = $xml->flush();
        Log::info($response);
        return response( $response, 200)->header('Content-Type', 'text/xml');
    }

    private function blacklisted($clid) {
        if ($clid === "+17808083320") {
		return true;
	} else {
		return false;
	}
    }

    private function sanitize($clid) {
	    return $clid;
    }
}
