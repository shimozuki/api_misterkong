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
        $offset = $request->offset;
        $lat = $request->lat;
        $lng = $request->lng;

        $user = DB::table('misterkong_log_webview.l_webview_mp')->where('token', $token)->first();

        if (empty($user)) {
            return response()->json([
                'message'   => 'token kosong'
            ], 404);
        } else {
            $query = "SELECT nearest.*,status_buka_toko FROM(
                select id, m_user_company.company_id, nickname_usaha, nama_usaha, (
                        3959 * acos (
                          cos ( radians(koordinat_lat) )
                          * cos( radians( " . $lat . ") )
                          * cos( radians( " . $lng . ") - radians(koordinat_lng) )
                          + sin ( radians(koordinat_lat) )
                          * sin( radians(" . $lat . ") )
                        )
                      ) AS distance,
                      gambar
                      from 
                      (
                        SELECT id, company_id, nickname_usaha, nama_usaha,koordinat_lat,koordinat_lng FROM m_user_company where (kategori_usaha = 2 or kategori_usaha = 7) and status = 1 
                      )m_user_company 
                      INNER JOIN 
              (
                SELECT company_id,GROUP_CONCAT(nama) AS item,gambar FROM m_barang 
                INNER JOIN(
                  SELECT barang_id,gambar	FROM m_barang_gambar WHERE gambar <> ''
                ) gambar
                ON gambar.barang_id = m_barang.id
                WHERE `status` = 2
                GROUP BY company_id -- ambil semua menu ditoko
              )
              m_barang ON m_barang.company_id=m_user_company.id
                    
                      HAVING distance < 30
                ) nearest
                INNER JOIN v_status_buka_toko ON v_status_buka_toko.id=nearest.id
                WHERE status_buka_toko = 1
                order by distance
                LIMIT 10 OFFSET $offset
                ";
            $restos = DB::select($query);
            return response()->json([
                'message'   => 'success',
                'data'      => $restos
            ], 200);
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
