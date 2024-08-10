<?php

namespace App\Services;

use Aws\Credentials\Credentials;
use Aws\Polly\PollyClient;

class Polly
{
    public function generateMp3($text_string) {
        $client = new PollyClient([
            'version'       => '2016-06-10',
            'credentials'   => new Credentials(
                config('services.aws_polly.key'), config('services.aws_polly.secret')
            ),
            'region'        => config('services.aws_polly.region'),
        ]);
        $result = $client->synthesizeSpeech([
            'OutputFormat'  => 'mp3',
            'Text'          => "<speak>" . $text_string . "</speak>",
            'TextType'      => 'ssml',
            'Engine'        => config('services.aws_polly.engine'),
            'VoiceId'       => config('services.aws_polly.voiceid'),
        ]);
        return $result->get('AudioStream')->getContents();
    }

}
