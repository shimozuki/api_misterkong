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
	
	public function up_driver($id_pesanan, $driver, $resi)
	{
		$date = date('Y-m-d H:i:s');
        try {
            DB::beginTransaction();
            DB::table('t_penjualan')->where('id', $id_pesanan)->update(['status_barang' => 4]);
            DB::table('t_pengiriman')->where('no_penjualan', $id_pesanan)->update(['id_driver' => $driver, 'no_resi' => $resi]);
            DB::table('t_pengiriman_status')->insert(['no_resi' => $resi, 'status' => 0, 'keterangan' => 'pesananmu sudah di terima nih, tunggu drivernya sampai ya']);
            $user = DB::table('t_penjualan')->select('user_id_pembeli')->where('id', $id_pesanan)->first();
            DB::table('t_chat_driver')->insert(['no_transaksi' => $resi, 'send_driver' => $driver, 'send_user' => $user->user_id_pembeli, 'read_driver' => 1, 'up_time' => $date]);
            DB::table('t_chat_driver_detail')->insert(['message_id' => $resi, 'message' => 'Hai! Pesananmu sudah diterima driver, driver akan segera meluncur ke restoran ya', 'sender' => 'driver', 'time_send' => $date, 'time_receive' => null, 'time_sent' => null, 'time_read' => $date, 'url' => null, 'status_delete_to' => 0, 'status_delete_by' => 0]);
            DB::commit();
            $payload = array(
				'to' => '/topics/general',
				'priority' => 'high',
				"mutable_content" => true,
				'data' => array(
					"id_driver" => $driver,
					"id_cust" => $user->user_id_pembeli,
					"msg" => 'Hai! Pesananmu sudah diterima driver, driver akan segera meluncur ke restoran ya'
				)
			);
			$headers = array(
				'Authorization:key=AAAAJrZwZQg:APA91bEp4BYq1kZcVwUyuh02a_s5F3txxf_CJHNbvdwsdjs6qwdHuWIiS3BKN7ETR3gtQkVZgHebKCH4C6N-QaHeJTEC5m8pMT0MDD5i6oG2bqPwbPT3XR3dY9h_zku1TtamNt9_Tn9q', 'Content-Type: application/json',
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
			$result = curl_exec($ch);
			curl_close($ch);
            return response([
                'message' => 'Berhasil mengirim Notif',
                'status' => 'Success'
            ], 400);
        } catch (\Exception $exp) {
            DB::rollBack(); 
            return response([
                'message' => $exp->getMessage(),
                'status' => 'failed'
            ], 400);
        }
	}
}