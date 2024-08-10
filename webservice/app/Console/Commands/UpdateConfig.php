<?php

namespace App\Console\Commands;

use App\Services\Polly;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class UpdateConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update DID Blocking Rules and Generate MP3';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $polly = new Polly();
        $json = Storage::get('dids.json');
        $dids = json_decode($json, true);
        foreach ($dids AS $config) {
            $this->info($config['did'] . ' - ' . json_encode($config));
            Cache::put('did_config_' . $config['did'], $config);
            if (isset($config['pass_recording_text']) && strlen($config['pass_recording_text'] ) > 0) {
                Storage::drive('public')->put(
                    md5($config['did'] . $config['pass_recording_text']) . ".mp3",
                    $polly->generateMp3($config['pass_recording_text']));
            }
            if (isset($config['fail_recording_text']) && strlen($config['fail_recording_text'] ) > 0) {
                Storage::drive('public')->put(
                    md5($config['did'] . $config['fail_recording_text']) . ".mp3",
                    $polly->generateMp3($config['fail_recording_text']));
            }
        }

        foreach (config('app.audio_file') AS $key => $text) {
            Storage::drive('public')->put(
                md5("config_{$key}") . ".mp3",
                $polly->generateMp3($text)
            );
        }
    }


}
