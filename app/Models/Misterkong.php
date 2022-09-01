<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;   
use Laravel\Sanctum\PersonalAccessToken as Model;

class Misterkong extends Model
{
    use HasFactory, HasApiTokens, Notifiable;
    protected $table = 'm_userx';
    protected $fillable = [
    ];
}
