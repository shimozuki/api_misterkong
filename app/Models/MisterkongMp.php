<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MisterkongMp extends Model
{

	public function getDataCheckOut($cid){
		$sql="CALL p_get_data_checkout('$cid')";
		return DB::select($sql)[0];
	}	
}