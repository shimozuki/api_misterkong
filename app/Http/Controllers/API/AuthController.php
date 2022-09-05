<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\Misterkong;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $user = $request->user;
        $password = $request->password;
        $imei = $request->imei;
        if (strpos($user, "@")) {
            $login_mail = $this->login_mail($user, $password, $imei);
            return response()->json([
                'data' => $login_mail
            ]);
        }
        $no_hp = str_split($user)[0] === '0' ? '62' . substr($user, 1): $user;
        $login_ph = $this->login_ph($no_hp, $password, $imei);
        return response()->json([
            'data' => $login_ph
        ]);
    }
    public function login_mail($email, $password, $imei)
    {
        $user = Misterkong::table('m_userx')->where('email', $email)->where('passwd', $password)->where('status', 1)->first();
        
        if (empty($user)) {
            return response()->json([
                'success' => false, 
                'msg' => 'Email atau Password salah'
            ]);
        }else {
            $token = hash('sha256', $plainTextToken = Str::random(40));
            $array = ['imei' => $imei, 'token' => $token,
                'user_id' => $user->id,
                'nama' => $user->nama,
                'toko' => '-',
                'jenis_user' => $user->jenis_user,
                'email' => $user->email,
                'login_stats' => 1,
            ];
            $insert = DB::table('misterkong_log_webview.l_webview_mp')->updateOrInsert(['imei' => $imei], $array);
            return response()->json([
                'message'   => 'success',
                'user'      => $user,
                'token'      => $token,
            ], 200);
        }
    }
    public function login_ph($no_hp, $password, $imei)
    {
        // dd(\DB::getQueryLog());
        $user = Misterkong::where('no_hp', '=', $no_hp, 'AND', 'passwd', '=', $password, 'AND', 'status', '=', 1)->first();
        // dd(\DB::getQueryLog());
        // print_r($user);
        if (empty($user)) {
            return response()->json([
                'success' => false, 
                'msg' => 'nomor hp atau Password salah'
            ]);
        }else {
            $token = hash('sha256', $plainTextToken = Str::random(40));
            $array = ['imei' => $imei, 'token' => $token,
                'user_id' => $user->id,
                'nama' => $user->nama,
                'toko' => '-',
                'jenis_user' => $user->jenis_user,
                'email' => $user->email,
                'login_stats' => 1,
            ];
            $insert = DB::table('misterkong_log_webview.l_webview_mp')->updateOrInsert(['imei' => $imei], $array);
            return response()->json([
                'message'   => 'success',
                'user'      => $user,
                'token'      => $token,
            ], 200);
        }
    }
    public function terdekat(Request $request)
    {
        $token = $request->token;
        $offset = $request->offset ?? 0;
        $lat = $request->lat ?? 0;
        $lng = $request->lng ?? 0;

        $user = DB::table('misterkong_log_webview.l_webview_mp')->where('token', $token)->first();

        if (empty($user)) {
            return response()->json([
                'message'   => 'token kosong'
            ], 404);
        } else {
            $array = ([$offset, $lat, $lng]);
            // DB::enableQueryLog();
            $restos = DB::select('call p_get_toko_terdekat(?,?,?)', $array);
            // dd(\DB::getQueryLog());
            return response()->json([
                'message'   => 'success',
                'data'      => $restos
            ], 200);
        }
    }
    public function terlaris(Request $request)
    {
        $token = $request->token;
        $offset = $request->offset ?? 0;
        $lat = $request->lat ?? 0;
        $lng = $request->lng ?? 0;

        $query = "SELECT 
        m_user_company.id, 
        m_user_company.company_id,
        nama_usaha, 
        jml_transaksi,
        (
          3959 * ACOS (COS (RADIANS(koordinat_lat))
          * COS(RADIANS(" . $lat . "))
          * COS(RADIANS(" . $lng . ") - RADIANS(koordinat_lng))
          + SIN (RADIANS(koordinat_lat))
          * SIN(RADIANS(" . $lat . "))
          )
        ) AS distance 
      FROM m_user_company 
      INNER JOIN 
        (SELECT COUNT(no_transaksi) AS jml_transaksi, user_id_toko FROM t_penjualan WHERE jenis_transaksi='FOOD' GROUP BY user_id_toko ORDER BY jml_transaksi DESC)
        t_penjualan ON m_user_company.id = t_penjualan.user_id_toko
      INNER JOIN 
        (SELECT company_id FROM m_barang INNER JOIN (SELECT barang_id FROM m_barang_gambar WHERE gambar <> '') gambar ON m_barang.id = gambar.barang_id WHERE `status` = 2 GROUP BY company_id) 
        barang_gambar ON m_user_company.id = barang_gambar.company_id	 
      WHERE (kategori_usaha = 2 OR kategori_usaha = 7) and status = 1 
      HAVING distance < 30
      LIMIT 10 OFFSET $offset";

        $restos = DB::select(DB::raw($query));

        if ($restos > 0) {
            return response()->json([
                'message'   => 'success',
                'data'      => $restos
            ], 200);
        }else {
            return response()->json([
                'message'   => 'Terjadi Kesalahan',
                'data'      => []
            ], 201);
        }
    }
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->json([
                'message'   => 'Berhasil LogOut'
            ], 200);
    }
}
