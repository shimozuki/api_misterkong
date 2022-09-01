<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\PersonalAccessToken as Model;
 
class PersonalAccessToken extends Model
{
    protected $table = 'misterkong_log_webview.l_webview_mp';
    protected $fillable = [
        'imei',
        'token',
        'user_id',
	    'nama',
	    'toko',
	    'jenis_user',
	    'email',
	    'login_stats',
    ];
}