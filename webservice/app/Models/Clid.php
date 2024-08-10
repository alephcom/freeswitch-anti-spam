<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clid extends Model
{
    use HasFactory;

    const STATUS_NONE = 0;
    const STATUS_WHITELISTED = 1;
    const STATUS_BLACKLISTED = 2;

}
