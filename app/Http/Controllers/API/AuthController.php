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
        $no_hp = str_split($user)[0] === '0' ? '62' . substr($user, 1) : $user;
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
        } else {
            $token = hash('sha256', $plainTextToken = Str::random(40));
            $array = [
                'imei' => $imei, 'token' => $token,
                'user_id' => $user->id,
                'nama' => $user->nama,
                'toko' => '-',
                'jenis_user' => $user->jenis_user,
                'email' => $user->email,
                'login_stats' => 1,
            ];
            $insert = DB::table('misterkong_log_webview.l_webview_mp')->updateOrInsert(['imei' => $imei], $array);
            return response()->json([
                'msg'   => 'success',
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
        } else {
            $token = hash('sha256', $plainTextToken = Str::random(40));
            $array = [
                'imei' => $imei, 'token' => $token,
                'user_id' => $user->id,
                'nama' => $user->nama,
                'toko' => '-',
                'jenis_user' => $user->jenis_user,
                'email' => $user->email,
                'login_stats' => 1,
            ];
            $insert = DB::table('misterkong_log_webview.l_webview_mp')->updateOrInsert(['imei' => $imei], $array);
            return response()->json([
                'msg'   => 'success',
                'user'      => $user,
                'token'      => $token,
            ], 200);
        }
    }
    public function terdekat(Request $request)
    {
        // $token = $request->token;
        $offset = $request->offset ?? 0;
        $lat = $request->lat ?? 0;
        $lng = $request->lng ?? 0;

        $array = ([$offset, $lat, $lng]);
        // DB::enableQueryLog();
        $restos = DB::select('call p_get_toko_terdekat(?,?,?)', $array);
        // dd(\DB::getQueryLog());
        return response()->json([
            'msg'   => 'success',
            'data'      => $restos
        ], 200);
    }
    public function terlaris(Request $request)
    {
        $offset = $request->offset ?? 0;
        $lat = $request->lat ?? 0;
        $lng = $request->lng ?? 0;
        $query = "SELECT 
                            m_user_company.alamat,
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
                            ) AS distance, gambar,buka,tutup, status_buka_toko FROM m_user_company 
                            INNER JOIN 
                                (SELECT COUNT(no_transaksi) AS jml_transaksi, user_id_toko FROM t_penjualan WHERE jenis_transaksi='FOOD' GROUP BY user_id_toko ORDER BY jml_transaksi DESC)
                                t_penjualan ON m_user_company.id = t_penjualan.user_id_toko
                            INNER JOIN 
                                (SELECT company_id, gambar FROM m_barang INNER JOIN (SELECT barang_id, gambar FROM m_barang_gambar WHERE gambar <> '') gambar ON m_barang.id = gambar.barang_id WHERE `status` = 2 GROUP BY company_id) 
                                barang_gambar ON m_user_company.id = barang_gambar.company_id	 
                                                    INNER JOIN (
                                                        SELECT id,status_buka_toko,buka,tutup FROM v_status_buka_toko WHERE status_buka_toko = 1
                                                    ) v_status_buka_toko ON v_status_buka_toko.id=m_user_company.id
                            WHERE (kategori_usaha = 2 OR kategori_usaha = 7) and status = 1 
                            HAVING distance < 30
                            LIMIT 10 OFFSET $offset";

        $restos = DB::select(DB::raw($query));

        if ($restos > 0) {
            return response()->json([
                'msg'   => 'success',
                'data'      => $restos
            ], 200);
        } else {
            return response()->json([
                'msg'   => 'Terjadi Kesalahan',
                'data'      => []
            ], 201);
        }
    }
    public function terbaru(Request $request)
    {
        $offset = $request->offset ?? 0;
        $lat = $request->lat ?? 0;
        $lng = $request->lng ?? 0;

        $array = ([$lat, $lng, $offset]);
        $query = DB::select('call p_company_baru(?,?,?)', $array);
        if (!empty($query)) {
            return response()->json([
                'msg'   => 'success',
                'data'      => $query
            ], 200);
        } else {
            return response()->json([
                'msg'   => 'Terjadi Kesalahan',
                'data'      => []
            ], 201);
        }
    }
    public function populer(Request $request)
    {
        $offset = $request->offset ?? 0;
        $lat = $request->lat ?? -8.5769951;
        $lng = $request->lng ?? 116.1004894;

        $query = "SELECT m_user_company.alamat, m_user_company.id,m_user_company.nama_usaha,m_user_company.company_id,status_buka_toko,
		(
				3959 * acos (
					cos ( radians(koordinat_lat) )
					* cos( radians( " . $lat . ") )
					* cos( radians( " . $lng . ") - radians(koordinat_lng) )
					+ sin ( radians(koordinat_lat) )
					* sin( radians(" . $lat . ") )
				)
			) AS distance, gambar, buka, tutup
		FROM m_user_company 
		INNER JOIN (
			SELECT m_barang.company_id, m_barang_gambar.gambar FROM m_barang INNER JOIN m_barang_gambar ON m_barang.id=m_barang_gambar.barang_id
			WHERE gambar<>'' AND `status`= 2 GROUP BY m_barang.company_id
		) barang ON barang.company_id=m_user_company.id
		INNER JOIN v_status_buka_toko
		ON v_status_buka_toko.id=m_user_company.id
		where kategori_usaha in (2,7) and m_user_company.status = 1
		GROUP BY id
		limit 10 OFFSET $offset";
        $restos = DB::select(DB::raw($query));
        if ($restos > 0) {
            return response()->json([
                'msg'   => 'success',
                'data'      => $restos
            ], 200);
        } else {
            return response()->json([
                'msg'   => 'Terjadi Kesalahan',
                'data'      => []
            ], 201);
        }
    }
    public function search_menu(Request $request)
    {
        $id = $request->id_toko;
        $query = "select company_id, id, nama from m_kategori where company_id =" . $id . " order by 
        company_id,
        id";
        $row = DB::select(DB::raw($query));
        if (!empty($row)) {
            return response()->json([
                'msg'   => 'success',
                'data'      => $row
            ], 200);
        }
    }
    public function fav(Request $request)
    {
        $toko = $request->toko;
        $token = $request->token;
        $barang = $request->barang;
        $user = DB::table('misterkong_log_webview.l_webview_mp')->where('token', $token)->first();
        $id = $user->user_id;

        if (!empty($user)) {
            $array = ['kd_user' => $id, 'kd_toko' => $toko, 'kd_barang_satuan' => $barang];
            $insert = DB::table('t_favorite_food')->insert($array);
        }
    }
    public function favorite(Request $request)
    {
        $token = $request->token;
        $user = DB::table('misterkong_log_webview.l_webview_mp')->where('token', $token)->first();
        $id = $user->user_id;

        if (empty($user)) {
            return response()->json([
                'msg'   => 'token kosong'
            ], 404);
        } else {
            $query = "select m_user_company.id, m_user_company.nama_usaha, 
            m_user_company.company_id, m_kategori_usaha.nama 
            from t_favorite_food inner join m_user_company on m_user_company.id = t_favorite_food.kd_toko
             inner join m_kategori_usaha on m_user_company.kategori_usaha = m_kategori_usaha.kd_kategori_usaha 
             where t_favorite_food.kd_user = $id group by t_favorite_food.kd_toko";
            $user_fav = DB::select(DB::raw($query));
            if (empty($user_fav)) {
                return response()->json([
                    'msg'   => 'data kosong',
                    'data'      => []
                ], 201);
            } else {
                return response()->json([
                    'msg'   => 'success',
                    'data'      => $user
                ], 200);
            }
        }
    }
    public function storeDetail(Request $request)
    {
        $id = $request->id;
        $lat = $request->lat ?? -8.5769951;
        $long = $request->long ?? 116.1004894;
        $token = $request->token;
        $user = DB::table('misterkong_log_webview.l_webview_mp')->where('token', $token)->first();
        if (empty($user)) {
            return response()->json([
                'msg'   => 'token kosong'
            ], 404);
        } else {
            $query = "SELECT m_user_company.id as id,m_user_company.company_id, m_user_company.nama_usaha, header, alamat, status_buka_toko, koordinat_lat, koordinat_lng ,(
                3959 * acos (
                    cos ( radians(koordinat_lat) )
                    * cos( radians( " . $lat . ") )
                    * cos( radians( " . $long . ") - radians(koordinat_lng) )
                    + sin ( radians(koordinat_lat) )
                    * sin( radians(" . $lat . ") )
                )
            ) AS distance FROM m_user_company   
            INNER JOIN v_status_buka_toko
            ON v_status_buka_toko.id=m_user_company.id
            WHERE m_user_company.id=" . $id;
            $action = DB::select(DB::raw($query));
            if (!empty($action)) {
                return response()->json([
                    'msg'   => 'success',
                    'data'      => $action
                ], 200);
            } else {
                return response()->json([
                    'msg'   => 'data kosong',
                    'data'      => []
                ], 201);
            }
        }
    }
    public function menu(Request $request)
    {
        $id = $request->id_toko;
        $user_id = $request->user_id;
        $id_barang_satuan = $request->barang_satuan;
        $query = "SELECT * FROM
		(select company_id,id,nama from m_kategori WHERE company_id = " . $id . ") kategori
		INNER JOIN
		(SELECT kategori_id,COUNT(kategori_id) AS jumlah_item FROM
		(SELECT id,kategori_id FROM m_barang WHERE company_id = " . $id . " AND status=2) barang
		INNER JOIN
		(SELECT barang_id FROM m_barang_satuan WHERE company_id =" . $id . " AND status=1) mbs
		ON mbs.barang_id=barang.id
		GROUP BY kategori_id) barang_satuan
		ON barang_satuan.kategori_id=kategori.id";
        $row  = DB::select(DB::raw($query));

        if (!empty($row)) {
            $query_d = "SELECT a.nama_usaha, a.idbrg, a.barang, a.keterangan, a.status_brg, 
            a.harga_jual, a.jumlah, a.satuan, a.kat, a.tag, a.stok, a.is_promo, a.gambar, 
            (select COUNT(kd_user) from t_favorite_food where kd_barang_satuan = a.id AND kd_user = " . $user_id . ")
            fav, 
				case 
				when ISNULL(varian.jml_varian) then 0 ELSE 1
				 END AS status_varian
				from v_food_list a
				LEFT JOIN 
				(
					SELECT barang_satuan_id, COUNT(barang_satuan_id) AS jml_varian FROM m_barang_satuan_varian GROUP BY barang_satuan_id
				) AS varian
				ON a.id = varian.barang_satuan_id
            WHERE a.company_id=" . $id . " order by stok desc limit 30";
            $result = DB::select(DB::raw($query_d));
            foreach ($result as $key => $value) {
                $status_varian = $value->status_varian;
            }
            if ($status_varian == 1) {
                // DB::enableQueryLog();
                $query_varian = DB::table('m_barang_satuan_varian')
                    ->select('m_varian.nama_varian', 'm_varian_details.nama', 'm_varian_details.harga', 'm_varian_details.no_urut', 'm_varian_details.keterangan')
                    ->join('m_varian', 'm_barang_satuan_varian.varian_id', '=', 'm_varian.id')
                    ->join('m_varian_details', 'm_varian.id', '=', 'm_varian_details.varian_id')
                    ->where('m_barang_satuan_varian.barang_satuan_id', '=', $id_barang_satuan)->get();
                // dd(DB::getQueryLog());
                return response()->json([
                    'msg'   => 'success',
                    'id_toko' => $id,
                    'data'      => $query_varian
                ], 200);
            }else {
                return response()->json([
                    'msg'   => 'success',
                    'id_toko' => $id,
                    'data'      => $result
                ], 200);
            }
        } else {
            return response()->json([
                'msg'   => 'data kosong',
                'data'      => []
            ], 200);
        }
    }
    public function varian(Request $request)
    {
        $id = $request->id_toko;
        $user_id = $request->user_id;
        $id_barang_satuan = $request->barang_satuan;
        $query_d = "SELECT a.nama_usaha, a.idbrg, a.barang, a.keterangan, a.status_brg, 
            a.harga_jual, a.jumlah, a.satuan, a.kat, a.tag, a.stok, a.is_promo, a.gambar, 
            (select COUNT(kd_user) from t_favorite_food where kd_barang_satuan = a.id AND kd_user = " . $user_id . ")
            fav, 
				case 
				when ISNULL(varian.jml_varian) then 0 ELSE 1
				 END AS status_varian
				from v_food_list a
				LEFT JOIN 
				(
					SELECT barang_satuan_id, COUNT(barang_satuan_id) AS jml_varian FROM m_barang_satuan_varian GROUP BY barang_satuan_id
				) AS varian
				ON a.id = varian.barang_satuan_id
            WHERE a.company_id=" . $id . " order by stok desc limit 30";
        $result = DB::select(DB::raw($query_d));

        foreach ($result as $key => $value) {
            $status_varian = $value->status_varian;
        }
        if ($status_varian == 1) {
            // DB::enableQueryLog();
            $query_varian = DB::table('m_barang_satuan_varian')
                ->select('m_varian.nama_varian', 'm_varian_details.nama', 'm_varian_details.harga', 'm_varian_details.no_urut', 'm_varian_details.keterangan')
                ->join('m_varian', 'm_barang_satuan_varian.varian_id', '=', 'm_varian.id')
                ->join('m_varian_details', 'm_varian.id', '=', 'm_varian_details.varian_id')
                ->where('m_barang_satuan_varian.barang_satuan_id', '=', $id_barang_satuan)->get();
            // dd(DB::getQueryLog());
            return response()->json([
                'msg'   => 'success',
                'id_toko' => $id,
                'data'      => $query_varian
            ], 200);
        } else {
            return response()->json([
                'msg'   => 'success',
                'id_toko' => $id,
                'data'      => 'tidak ada varian'
            ], 200);
        }
    }
    public function cari(Request $request)
    {
        $lat = $request->lat ?? -8.59597203;
        $lng = $request->lng ?? 116.1058106;
        $key = $request->key;

        $query = "SELECT nearest.*,status_buka_toko,buka,tutup FROM
        (SELECT m_user_company.*,m_barang.item FROM 
                (
                  SELECT *,
                  ROUND(
                    3959 * ACOS(
                      COS(RADIANS(koordinat_lat)) * 
                      COS(RADIANS($lat)) * 
                      COS(RADIANS($lng)- 
                      RADIANS( koordinat_lng)) + 
                      SIN(RADIANS(koordinat_lat )) * 
                      SIN(RADIANS($lat)) 
                    ) 
                  ,2) AS distance
                  FROM 
                  m_user_company WHERE kategori_usaha=2 
                )m_user_company
                INNER JOIN 
                (
                  SELECT company_id,GROUP_CONCAT(nama) AS item FROM m_barang 
                        INNER JOIN(
                            SELECT barang_id,gambar	FROM m_barang_gambar WHERE gambar <> '' 
                        ) gambar
                        ON gambar.barang_id = m_barang.id
                  WHERE `status` =2
                        GROUP BY company_id 
                )
                m_barang ON m_barang.company_id=m_user_company.id
                WHERE m_user_company.nama_usaha LIKE '%$key%' OR item LIKE '%$$key%'
                  ) nearest 
                  INNER JOIN v_status_buka_toko ON v_status_buka_toko.id=nearest.id
                    WHERE status_buka_toko = 1
                    order by distance";

        $result = DB::select(DB::raw($query));
        if (!empty($result)) {
            return response()->json([
                'msg'   => 'success',
                'data'      => $result
            ], 200);
        } else {
            return response()->json([
                'msg'   => 'gagal',
                'data'      => []
            ], 200);
        }
    }
    public function kongjek(Request $request)
    {
        // $request = json_decode(file_get_contents('php://input'), true);
        $lat_resto = $request->lat_resto;
        $lng_resto = $request->lng_resto;
        $lat_dets = $request->lat_dest;
        $lng_dets = $request->lng_dest;
        $imei = $request->imei;
        $total = $request->total;
        $user = DB::table('misterkong_log_webview.l_webview_mp')->select('user_id')->where('imei', $imei)->first();
        $id_user = $user->user_id;
        $get_distance = $this->http_request("https://router.project-osrm.org/route/v1/driving/" . $lng_resto . "," . $lat_resto . ";" . $lng_dets . "," . $lat_dets . "?overview=false&alternatives=true&steps=true&hints=;");
        $respon = json_decode($get_distance, true);
        $distance = $respon['routes'][0]['distance'];

        $getRiders =  $this->http_request("https://misterkong.com/kajek/services/cari_driver.php?auth=appKeyAuth&restoLat=" . $lat_resto . "&restoLng=" . $lng_resto . "&desLat=" . $lat_dets . "&desLng=" . $lng_dets . "&kd_user=" . $id_user . "&rad=25&distan=" . $distance . "&total=" . $total);
        $riders = json_decode($getRiders);

        if ($riders == null) {
            return response()->json([
                'success' => false,
            ], 201);
        } else {
            return response()->json([
                "success" => true,
                "riders" => $riders,
                "distance" => $distance
            ]);
        }
    }
    function http_request($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        // return the transfer as a string 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // $output contains the output string 
        $output = curl_exec($ch);

        // tutup curl 
        curl_close($ch);

        // mengembalikan hasil curl
        return $output;
    }
    public function info_rider(Request $request)
    {
        $id_user = $request->id_rider;
        $query = DB::table('m_driver_kendaraan')->select('m_driver_kendaraan.nomor_plat', 'm_driver.nama_depan', 'm_merk_kendaraan.merk_nama', 'm_driver_kendaraan.STNK_expired', 'm_model_kendaraan.model_nama', 'm_driver.sim_exp')
            ->join('m_driver', 'm_driver_kendaraan.kd_driver', '=', 'm_driver.kd_driver')
            ->join('m_merk_kendaraan', 'm_driver_kendaraan.kd_merk', '=', 'm_merk_kendaraan.merk_id')
            ->join('m_model_kendaraan', 'm_driver_kendaraan.kd_model', '=', 'm_model_kendaraan.model_id')
            ->where('m_driver_kendaraan.status', '2')->where('m_driver.kd_driver', $id_user)->first();
        if (!empty($query)) {
            return response()->json([
                'success' => true,
                'data'   => $query
            ], 200);
        } else {
            return response()->json([
                "success" => false,
                'data' => []
            ], 201);
        }
    }
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'msg'   => 'Berhasil LogOut'
        ], 200);
    }
}
