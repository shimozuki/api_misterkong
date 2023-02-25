<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\Misterkong;
use App\Models\MisterkongMp;
use DateTime;
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
                            m_user_company.koordinat_lat,
                            m_user_company.koordinat_lng,
                            nama_usaha, 
                            jml_transaksi,
                            (
                            6378 * ACOS (COS (RADIANS(koordinat_lat))
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

        $query = "SELECT  m_user_company.koordinat_lat, m_user_company.koordinat_lng, m_user_company.alamat, m_user_company.id,m_user_company.nama_usaha,m_user_company.company_id,status_buka_toko,
		(
				6378 * acos (
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
            $query = "select m_user_company.id, m_user_company.nama_usaha,  m_user_company.koordinat_lat, m_user_company.koordinat_lng,
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
                6378 * acos (
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
            a.harga_jual, a.jumlah, a.satuan, a.kat, a.tag, a.stok, a.is_promo, a.gambar, a.id,
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
            if (!empty($id_barang_satuan)) {
                $query_cek = "SELECT a.nama_usaha, a.idbrg, a.barang, a.keterangan, a.status_brg, 
                a.harga_jual, a.jumlah, a.satuan, a.kat, a.tag, a.stok, a.is_promo, a.gambar, a.id,
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
                WHERE a.company_id=" . $id . " AND a.id = " . $id_barang_satuan . " order by stok desc limit 30";

                $raw = DB::select(DB::raw($query_cek));
                foreach ($raw as $key => $value) {
                    $status_varian = $value->status_varian;
                }
                if ($status_varian == 1) {
                    $qvarian = "SELECT mv.*,
                mvd.kd_varian_details,
                mvd.nama,
                mvd.harga,
                mvd.keterangan,
                mvd.reff,
                mvd.no_urut
                FROM 
                (
                    SELECT * FROM
                    m_barang_satuan_varian WHERE barang_satuan_id 
                    IN (" . $id_barang_satuan . ")
                )
                mbsv 
                INNER JOIN m_varian mv ON mbsv.varian_id=mv.id
                INNER JOIN 
                (
                    SELECT varian_id,
                    GROUP_CONCAT(kd_varian_details ORDER BY no_urut) AS kd_varian_details,
                    GROUP_CONCAT(nama ORDER BY no_urut) AS nama,
                    GROUP_CONCAT(harga ORDER BY no_urut) AS harga,
                    GROUP_CONCAT(keterangan ORDER BY no_urut) AS keterangan,
                    GROUP_CONCAT(reff ORDER BY no_urut) AS reff,
                    GROUP_CONCAT(no_urut ORDER BY no_urut) AS no_urut
                    FROM m_varian_details
                    GROUP BY varian_id
                ) mvd 
                ON mvd.varian_id=mv.id WHERE mv.status = 1";
                    // DB::enableQueryLog();
                    $output = [];
                    // DB::enableQueryLog();
                    $query_varian = DB::select(DB::raw($qvarian));
                    // dd(DB::getQueryLog());
                    foreach ($query_varian as $key => $value) {
                        $output[$key][] = $value;
                        $detailsv = $value->kd_varian_details;
                        $nama_detail = $value->nama;
                        $harga = $value->harga;
                        $keterangan = $value->keterangan;
                        $reff = $value->reff;
                        $kd_varian = $value->kd_varian;
                        $nama_varian = $value->nama_varian;
                        $statusv = $value->status_varian;
                        $maxvarian = $value->batas_maksimum;
                        $status_max = $value->status_max;
                        $min_varian = $value->jumlah_varian_min;
                        $kd_detail = explode(',', $detailsv);
                        $namadetail = explode(',', $nama_detail);
                        $hargad = explode(',', $harga);
                        $keterangand = explode(',', $keterangan);
                        $reffd = explode(',', $reff);
                        $detail = [];
                        for ($i = 0; $i < count($kd_detail); $i++) {
                            $detail[] = [
                                'kd_varian_detail' => $kd_detail[$i], 
                                'nama' => $namadetail[$i], 
                                'harga' => floatval($hargad[$i]), 
                                'keterangan' => $keterangand[$i], 
                                'reff' => intval($reffd[$i])
                            ];
                        }
                        $data_varian[] = ['kd_varian' => $kd_varian, 'nama' => $nama_varian, 'status_varian' => $statusv, 'jumlah_varian_min' => $min_varian, 'status_maximum' => $status_max, 'batas_maksimum' => $maxvarian, 'detail' =>  $detail];
                        //  $output[$key]['detail'] = $detail;
                    }
                    // dd(DB::getQueryLog());
                    // return response()->json([$output], 200);
                    return response()->json([
                        'msg'   => 'success',
                        'id_toko' => $id,
                        'data_variant' => $data_varian
                    ], 200);
                } else {
                    return response()->json([
                        'msg'   => 'success',
                        'id_toko' => $id,
                        'variant' => []
                    ], 202);
                }
            } else {
                return response()->json([
                    'msg'   => 'success',
                    'id_toko' => $id,
                    'data_barang' => $result,
                ], 200);
            }
        } else {
            return response()->json([
                'msg'   => 'data kosong',
                'data'      => []
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
                    6378 * ACOS(
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
    public function getongkir(Request $request)
    {
        $provinsi = $request->provinsi;
        $jarak = $request->jarak;
        $jenis_kndr = $request->jenis_kndr;
        $app_id = $request->appid;
        $get_zona = DB::table('m_driver_zona_lokasi')->select('kd_zona')->where('lokasi', $provinsi)->orwhere('lokasi_1', $provinsi)->first();
        if (empty($get_zona)) {
            return response()->json([
                'success' => false,
                'message' => 'Zona tidak ditemukan',
                'data'   => []
            ], 400);
        } else {
            $zona_id = $get_zona->kd_zona;
            $zona_driver = DB::table('m_driver_zona')->select('*')->where('zona_id', $zona_id)->where('app_id', $app_id)->where('jenis_kendaraan_id', $jenis_kndr)->first();
            if (empty($zona_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zona tidak ditemukan',
                    'data'   => []
                ], 400);
            }
            $gap = $jarak - $zona_driver->jarak_pertama;
            if ($gap <= 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'success',
                    'ongkir'   => $zona_driver->fee_minim_bawah + $zona_driver->biaya1
                ], 200);
            }
            $ongkir = $zona_driver->fee_minim_bawah + ($gap * $zona_driver->batas_bawah) + $zona_driver->biaya1;
            return response()->json([
                'success' => true,
                'message' => 'success',
                'ongkir'   => ceil($ongkir / 1000) * 1000
            ], 201);
        }
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
    public function chekout_old(Request $request)
    {
        $data = $request->data;
        $test[] = json_decode($data, true);
        $potongan = DB::table('m_potongan')->select('*')->where('id', 3)->first();
        $potonganToko = $potongan->jenis == 1 ? $potongan->nominal : ($potongan->nominal * $data['total']) / 100;
        $no_transaksi = $this->no_transaksi($test[0]['user_id']);
        // $lat = $test[0]['restoLatLn'];
        // return $test;
        try {
            DB::beginTransaction();
            $data_tpenjualan = [
                'no_transaksi'    => $no_transaksi,
                'tanggal'         => date('Y-m-d H:i:s'),
                'user_id_toko'    => $test[0]['restoId'],
                'user_id_pembeli' => $test[0]['user_id'],
                'status_barang'   => 0,
                'jenis_transaksi' => 'FOOD',
                'ppn'             => 0,
                'pph'             => 0,
                'ppnbm'           => 0,
                'pemesanan'       => 0,
                'lain_lain'       => 0,
                'status_review'   => 0,
                'biaya_aplikasi'  => $test[0]['biayaAplikasi'],
                'potongan_toko'   => $potonganToko,
            ];
            DB::table('t_penjualan')->insert($data_tpenjualan);
            $penjualanID = DB::getPdo()->lastInsertId();
            $oreder = $test[0]['order'];
            foreach ($oreder as $key => $value) {
                DB::table('t_penjualan_detail')->insert( [
                    'no_transaksi'  => $no_transaksi,
                    'item_id'       => $value['item_id'],
                    'qty'           => $value['qty'],
                    'harga_jual'    => $value['harga_jual'],
                    'diskon'        => $value['diskon'],
                    'keterangan'    => $value['notes'] ?? '-',
                    'penjualan_id'  => $penjualanID,
                ]);
            }
    
            DB::table('t_pengiriman')->insert([
                'no_penjualan' => $penjualanID,
                'nama_tujuan' => $test[0]['nama'],
                'origin_koordinat_lat' => $test[0]['restoLat'],
                'origin_koordinat_lng' => $test[0]['restoLng'],
                'dest_alamat' => $test[0]['deliveryAddress'],
                'dest_koordinat_lat' => $test[0]['deliveryLat'],
                'dest_koordinat_lng' => $test[0]['deliveryLng'],
                'status_pembayaran' => 0,
                'ongkir' => $test[0]['ongkir'],
                'kurir' => 'DRIVER',
                'layanan' => 'FOOD',
                'dest_keterangan' => $test[0]['deliveryNotes'] ?? "-",
                'date_add' => date('Y-m-d H:i:s'),
            ]);
    
            // insert penagihan
            $nopenagihan = $this->no_penagihan();
            DB::table('t_penagihan')->insert([
                'no_penagihan' => 'TAG2022111400091',
                'total_pembayaran' => $test[0]['total'],
                'total_diskon' => $test[0]['diskon'],
                'total_ongkir' => $test[0]['ongkir'],
                'jenis_pembayaran' => $test[0]['paymentMethod'],
                'status' => 0,
            ]);
            $penagihanId = DB::getPdo()->lastInsertId();
            
            DB::table('t_penagihan_detail')->insert([
                'no_penagihan' => $penagihanId,
                'no_transaksi_penjualan' => $penjualanID,
                'total' => $test[0]['total'],
                'diskon' => $test[0]['diskon'],
                'ongkir' => $test[0]['ongkir'],
              ]);
    
              DB::commit();
              return response([
                'message' => "created successfully",
                'status' => "success"
            ], 200);
        } catch (\Exception $exp) {
            DB::rollBack(); 
            return response([
                'message' => $exp->getMessage(),
                'status' => 'failed'
            ], 400);
        }
    }

    public function chekout(Request $request)
    {

        $data_post=$request->all();
        $data_save=[];
        $detail=[];
        $no_transaksi = $this->no_transaksi($data_post['user_id']);

        $data = MisterkongMp::getDataCheckOut($data_post['toko_id']);   


        $no_urut=1;
        foreach ($data_post['data_trans'] as $key_dt => $value_dt) {
            if (!empty($value_dt['data_varian']))  {
                
                $detail[]=array(
                    'no_transaksi' => $no_transaksi,
                    'item_id' =>$value_dt['idbrg'],
                    'qty' => $value_dt['qty'],
                    'harga_jual' => $value_dt['harga_jual'],
                    'diskon' =>0,
                    'keterangan' =>'-',
                    'penjualan_id' =>'',
                    'reff' =>'',
                    'group_id' =>$key_dt+1,
                    'no_urut' => $no_urut
                );

                foreach ($value_dt['data_varian'] as $key_varian => $value_varian) {
                    $barang_id_reff=$data->id;
                    if (!empty($value_varian['idBrg'])) {
                        $barang_id_reff=$value_varian['idBrg'];
                    }
                    $detail[]=array(
                        'no_transaksi' => $no_transaksi,
                        'item_id'=>$barang_id_reff,
                        'qty' => $value_dt['qty'],
                        'harga_jual' => $value_varian['harga'],
                        'diskon' =>0,
                        'keterangan' =>$value_varian['varNama'],
                        'penjualan_id' =>'',
                        'reff' =>$value_varian['reff'],
                        'group_id' =>$key_dt+1,
                        'no_urut' => $no_urut
                    );
                    $no_urut++;
                }
                
            }else{
                $detail[]=array(
                    'no_transaksi' => $no_transaksi,
                    'item_id' =>$value_dt['idbrg'],
                    'qty' => $value_dt['qty'],
                    'harga_jual' => $value_dt['harga_jual'],
                    'diskon' =>0,
                    'keterangan' =>'-',
                    'penjualan_id' =>'',
                    'reff' =>'',
                    'group_id' =>$key_dt+1,
                    'no_urut' => $no_urut
                );
            }
            $no_urut++;
        }

        $data_save['penjualan']=array(
            'no_transaksi'=>$no_transaksi,
            'tanggal'=>date('Y-m-d'),
            'user_id_toko' =>$data_post['toko_id'],
            'user_id_pembeli' =>$data_post['user_id'],
            'no_ref' =>'0',
            'lokasi_pengirim' =>'',
            'lokasi_tujuan' =>'',
            'status_barang' =>'0',
            // 'id_penagihan' =>'',
            // 'no_penagihan' =>'',
            'jenis_transaksi' =>'FOOD',
            'ppn' =>'0',
            'pph' =>'0',
            'ppnbm' =>'0',
            'pemesanan' =>'0',
            'lain_lain' =>'0',
            'status_review' =>'0',
            'biaya_aplikasi' =>$data->biaya_aplikasi,
            'potongan_toko' =>$data->potongan_toko,
        );
        $data_save['penjualan_detail']=$detail;
        try {
            DB::beginTransaction();
            $last_id = DB::table('t_penjualan')->insertGetId($data_save['penjualan']);
            for ($i=0; $i < count($data_save['penjualan_detail']); $i++) 
            {
                $data_save['penjualan_detail'][$i]['penjualan_id'] = $last_id;
            }   
            DB::table('t_penjualan_detail')->insert($data_save['penjualan_detail']);

            DB::commit();
        
            return response([
                'message' => "Created successfully",
                'status' => "success",
                'no_transaksi' => $no_transaksi
            ], 200);
        } catch (\Exception $exp) {
            DB::rollBack(); 
            return response([
                'message' => $exp->getMessage(),
                'status' => 'failed'
            ], 400);
        }
    }

    public function pecah(Request $request)
    {
        $data = $request->data;
        $test = json_decode($data, true);
        $array[] = [$test];
        return response([
            'message' => $array
        ], 400);

    }
    public function no_penagihan()
    {
        $query = "SELECT MAX(no_penagihan) notrans FROM t_penagihan";
        $exe_max_trans = DB::select(DB::raw($query));
        $nomor = $exe_max_trans;
        $noUrut = (int) substr('$nomor', -5);
        if ($exe_max_trans == 0) {
            $no_trans = "TAG" . date("Ymd") . "00001";
        } else {
            $noUrut++;
            $no_trans = "TAG" . date("Ymd") . sprintf("%05s", $noUrut);
        }
        return $exe_max_trans;
    }
    public function no_transaksi($user_id)
    {
        $date = new DateTime();
        $noTransaksi = 'F0' . sprintf('%04d', $user_id) . $date->format('ymdHisv');

        return substr($noTransaksi, 0, -1);
    }
    public function getRiderInfo(Request $request)
    {
        $id_rider = $request->id_rider;
        $query = "SELECT * FROM m_driver 
        INNER JOIN m_driver_kendaraan ON m_driver.kd_driver = m_driver_kendaraan.kd_driver WHERE m_driver_kendaraan.status = 2 AND m_driver.kd_driver = $id_rider";
        $getinfo_rider = DB::select(DB::raw($query));
        return response()->json([
            'success' => true,
            'data'   => $getinfo_rider
        ], 200);
    }
    public function penolakan(Request $request)
    {
        $data = [
            'id_penjualan' => $request->id_penjualan,
            'id_driver' => $request->id_driver,
            'tanggal' => date('Y-m-d H:i:s'),
            'status' => $request->status,
        ];

        $insert = DB::insert('t_penjualan_driver_batal', $data);

        if ($insert == TRUE) {
            return response()->json([
                'success' => true,
                'message' => 'Penolakan Berhasil'
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Penolakan gagal'
            ], 400);
        }
    }
    // public function getRider(Request $request)
    // {
    //     $lat = $request->lat;
    //     $lng = $request->lng;
    //     $jenis_kndr = $request->jenis_kndr;
    //     $total = $request->total;
    //     $id_penjualan = $request->id_penjualan;
    //     $radius = $request->radius;

    //     $query_getrider = "SELECT 
    //     md.kd_driver AS KODE_DRIVER,
    //     md.nama_depan AS NAMA_ DRIVER,
    //     md.hp1 AS NO_HP,
    //     mdk.nomor_plat AS PLAT_MOTOR,
    //     ROUND(
    //         6378 * ACOS(
    //             COS(RADIANS('" . $lat . "')) * COS(RADIANS( mdll.loc_lat )) * 
    //             COS(RADIANS( mdll.loc_lng )- RADIANS( '" . $lng . "' )) + 
    //             SIN(RADIANS( '" . $lat . "' )) * SIN(RADIANS( mdll.loc_lat )) 
    //         ),2
    //     ) AS JARAK_DRIVER,IFNULL(lf.qtytr,0) AS DAY_TRANS,
    //     msd.saldo AS SALDO
    //     FROM m_driver AS md
    //     INNER JOIN m_driver_location_log AS mdll ON mdll.kd_driver = md.kd_driver
    //     INNER JOIN m_driver_kendaraan as mdk ON mdk.kd_driver=md.kd_driver
    //     INNER JOIN m_saldo_driver as msd ON msd.kd_driver=md.kd_driver
    //     LEFT JOIN 
    //     (
    //         SELECT td.kd_driver,COUNT( td.kd_driver ) AS qtytr
    //         FROM t_driver AS td
    //         WHERE DATE( td.tanggal )= CURRENT_DATE
    //         GROUP BY td.kd_driver 		
    //     ) lf 
    //     ON md.kd_driver = lf.kd_driver 
    //     WHERE md.`status`=3 AND mdll.driver_state=1 AND mdk.`status`=2 AND msd.saldo >= '" . $total . "'
    //     AND kd_jenis_kendaraan= '" . $jenis_kndr . "' AND md.kd_driver NOT IN (SELECT id_driver FROM t_penjualan_driver_batal WHERE id_penjualan = '" . $id_penjualan . "' AND `status` = 1)
    //     HAVING JARAK_DRIVER <= '" . $radius . "'
    //     ORDER BY qtytr,JARAK_DRIVER
    //     LIMIT 0,10;";

    //     $getrider = DB::select(DB::raw($query_getrider));
    //     if (!empty($getrider)) {
    //         return response()->json([
    //             'success' => true,
    //             'msg'   => 'success',
    //             'data' => $getrider
    //         ], 200);
    //     } else {
    //         return response()->json([
    //             'success' => false,
    //             'msg'   => 'Gagal request'
    //         ], 500);
    //     }

    // }
    public function register(Request $request)
    {
        $email = $request->email;
        $no_hp = str_split($request->no_hp) [0] === '0' ? '62' . substr($request->no_hp, 1) : $request->no_hp;
        $cek_no_hp = DB::table('m_userx')->where('no_hp', $no_hp)->where('status', 1)->first();
        $cek_email = DB::table('m_userx')->where('email', $email)->where('status', 1)->first();
        if (!empty($cek_no_hp)) {
            return response()->json([
                'success' => false,
                'msg'   => 'sudah digunakan'
            ], 201);
        }elseif (!empty($cek_email)) {
            return response()->json([
                'success' => false,
                'msg'   => 'email sudah digunakan'
            ], 201);
        } else{
            $data = [
                'kd_group' => 2,
                'nama' => $request->nama,
                'passwd' => md5($request->password),
                'keterangan' => '-',
                'no_hp' => $no_hp,
                'status_phone' => '0',
                'email' => $email,
                'status_email' => '0',
                'jenis_user' => '0',
            ];
            $cek_no_hp_nonaktif = DB::table('m_userx')->where('no_hp', $no_hp)->where('status', 0)->first();
            try {
                if (!empty($cek_no_hp_nonaktif)) {
                    $simpan = DB::table('m_userx')->where('no_hp', $no_hp)->update($data);
                } else {
                    $ex_max_trans = DB::table('m_userx')->select('id')->orderBy('id', 'desc')->first();
                    $trans = $ex_max_trans;
                    $nomor = $trans->id;
                    if(empty($ex_max_trans))
                    {
                        $no_trans = 1;
                    }else{
                        $no_trans = $nomor + 1;
                    }
                    $ex_max_transkd = DB::table('m_userx')->select('kd_user')->orderBy('kd_user', 'desc')->first();
                    $transkd = $ex_max_transkd;
                    $nomorkd = $transkd->kd_user;
                    $nourut = (int) substr($nomor, -3);
                    if(empty($ex_max_transkd))
                    {
                        $no_transkd = "UAA001";
                    }else{
                        $nourut++;
                        $no_transkd = "UAA".sprintf("%30s", $nourut);
                    }
                    $data['id'] = $no_trans;
                    $data['kd_user'] = $no_transkd;
                    $simpan = DB::table('m_userx')->insert($data);
                }
                    return response()->json([
                        'success' => true,
                        'msg'   => 'Berhasil insert'
                    ], 200);
            } catch (\Throwable $th) {
                return response()->json([
                    'success' => false,
                    'msg'   => $th
                ], 205);
            }
            
        }
    }
    public function kirim_otp(Request $request)
    {
        $nohp = $request->no_hp;
        $no_hp = str_split($request->no_hp) [0] === '0' ? '62' . substr($request->no_hp, 1) : $request->no_hp;
        $array = ([$no_hp]);
        $cekOtpAttemps = DB::select('CALL misterkong_db_all_histori.get_request_otp_misterkong(?)', $array);
        $statusOtp = $cekOtpAttemps[0]->status_otp;

        $waktuRequest = date('Y-m-d H:i:s');
		$timeLimit = date('Y-m-d H:i:s', strtotime("+30 minutes", strtotime($waktuRequest)));
        
        if ($statusOtp == '0') {
            $req_lagi = DB::select('CALL misterkong_db_all_histori.get_request_otp_misterkong(?)', $array);
            $waktu = $req_lagi[0]->time_limit;
            return response()->json([
                'success' => false,
                'waktu'   => $waktu
            ], 201);
        }
        $from               = ""; //Sender ID or SMS Masking Name, if leave blank, it will use default from telco
		$apikey             = "dd4cfd6168564ae033110fa7ec0e66fd-4a8acf79-b3da-4063-8368-b8c9d124eb48"; //get your API KEY from our sms dashboard
		$postUrl            = "https://api.smsviro.com/restapi/sms/1/text/advanced"; # DO NOT CHANGE THIS

		$destination = array("to" => $no_hp);
		$otp = rand(100000, 999999);

		$message = array(
			"from" => $from,
			"destinations" => $destination,
			"text" => "<#> MisterKong Kode OTP anda adalah " . $otp . ", jangan pernah memberitahukan kode otp ini kepada siapapun"
		);

        // print_r($message);

		// update histori otp pos
		$simpanHistory = DB::table("misterkong_db_all_histori.h_misterkong_otp")->insert([
			"no_hp" => $no_hp,
			"imei" => "-",
			"otp" => $otp,
			"request_at" => $waktuRequest,
			"keterangan" => "-"
		]);

		$updateHistory = DB::table("misterkong_db_all_histori.h_log_misterkong_otp")->update([
			"time_request" => $waktuRequest,
			"time_limit" => $timeLimit
		], "no_hp = '$no_hp' AND time_limit < '$waktuRequest'");

		$postData           = array("messages" => array($message));
		$postDataJson       = json_encode($postData);
		$ch                 = curl_init();

		curl_setopt($ch, CURLOPT_URL, $postUrl);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', "Accept:application/json", 'Authorization: App ' . $apikey));
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataJson);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$responseBody = json_decode($response);
		curl_close($ch);
        return response()->json([
            'success' => true,
            'respon'   => $responseBody,
            'Otp' => $otp
        ], 200);
    }
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'msg'   => 'Berhasil LogOut', 
            
        ], 200);
    }
   public function notifOrder()
   {
        $data = json_decode(file_get_contents('php://input'), true);
        $potong  =  DB::table('m_potongan')->where('id', 2)->first();
        $data['potongan_rider'] = $potong->jenis == '1' ? $potong->nominal : ($potong->nominal / 100 * $data['ongkos']);
        $data['jenis_notif']  = 9;

        $destinasi = [
            'ios' => [
              'to' => '/topics/ios_general',
              'headers' => [
                "authorization:key=AAAAFLVl2_0:APA91bG9ce3PpSlf4cRjbbRIglt-6JsK_IcwxpXXkwC2oingJDVFSxncZ8PY3bNbfR8aZsIiq51nzQACLdhMQm1c7rTJciH_owB6mVUSM3gsrNc-ft0BxIluO6oEBN5-M1-GwNZBbADC",
                'Content-Type: application/json'
              ]
            ],
            'android' =>  [
              'to' => '/topics/kongRiderFCM',
              'headers' => [
                'Authorization:key=AAAAJrZwZQg:APA91bEp4BYq1kZcVwUyuh02a_s5F3txxf_CJHNbvdwsdjs6qwdHuWIiS3BKN7ETR3gtQkVZgHebKCH4C6N-QaHeJTEC5m8pMT0MDD5i6oG2bqPwbPT3XR3dY9h_zku1TtamNt9_Tn9q',
                'Content-Type: application/json'
              ]
            ],
          ];

          foreach ($destinasi as $key => $value) {
            $payload = array(
              'to' => $value['to'],
              'priority' => 'high',
              "mutable_content" => true,
              'data' => $data,
            );
      
            $headers = $value['headers'];
      
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            $result = curl_exec($ch);
            curl_close($ch);
          }
   }
}
