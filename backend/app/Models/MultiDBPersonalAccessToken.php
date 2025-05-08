<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\PersonalAccessToken;

class MultiDBPersonalAccessToken extends PersonalAccessToken
{
    protected $connection = 'mysql';
}
