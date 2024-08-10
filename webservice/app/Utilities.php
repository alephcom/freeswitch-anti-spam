<?php

namespace App;

use App\Models\Clid;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Utilities
{

    public static function blacklist($clid, $days) {
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

    public static function whitelist($clid, $days) {
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

    public static function sanitize($clid)
    {
        return preg_replace('/[^0-9]/','',$clid);
    }
}
