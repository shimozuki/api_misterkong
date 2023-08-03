<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiderController extends Controller
{
    public function get_transaction_for_driver(Request $request)
    {
        $resi = $request->resi;
        $query = DB::table('t_penjualan_detail')->join('m_barang_satuan', 't_penjualan_detail.item_id', '=', 'm_barang_satuan.id')
        ->join('m_barang', 'm_barang.id', '=', 'm_barang_satuan.barang_id')->leftJoin('m_barang_satuan_varian', 'm_barang_satuan.id', '=', 'm_barang_satuan_varian.barang_satuan_id')
        ->leftJoin('m_varian', 'm_barang_satuan_varian.varian_id', '=', 'm_varian.id')->where('t_penjualan_detail.no_transaksi', $resi)->select('m_barang.nama','t_penjualan_detail.qty','t_penjualan_detail.harga_jual', 'm_varian.nama_varian AS varian')->get();

        if (!empty($query)) {
            return response()->json([
                'status' => 1,
                'message' => 'Data ditemuka',
                'menu' => $query
            ]);
        }else{
            return response()->json([
                'status' => 0,
                'message' => 'Data tidak ditemukann',
                'data' => []
            ]);
        }

    }

    public function getimage()
    {
        $image = DB::table('g_bank_kong')->where('status', '!=', 0)->orderBy('status')->get();

        if (empty($image)) {
            return response()->json([
                'status' => 0,
                'message' => 'Data tidak ditemukann',
                'data' => []
            ], 500);
        }else {
            return response()->json([
                'status' => 1,
                'message' => 'Data ditemuka',
                'menu' => $image
            ], 200);
        }
    }

    public function getpict(Request $req)
    {
        $iddr = $req->iddr;
        $query = "SELECT 'avatar/' as pt, CONCAT(".$iddr.", 'avatar.jpg') as nm
        UNION
        SELECT 'skck/' as pt, CONCAT(".$iddr.", 'skck.jpg') as nm
        UNION
        SELECT 'ktp/' as pt, CONCAT(".$iddr.", 'ktp.jpg') as nm
        UNION
        SELECT 'sim/' as pt, CONCAT(".$iddr.", 'simA.jpg') as nm
        UNION
        SELECT 'sim/' as pt, CONCAT(".$iddr.", 'simB1.jpg') as nm
        UNION
        SELECT 'sim/' as pt, CONCAT(".$iddr.", 'simB2.jpg') as nm
        UNION
        SELECT 'sim/' as pt, CONCAT(".$iddr.", 'simC.jpg') as nm
        UNION
        SELECT 'kendaraan/' as pt, CONCAT(a.file) as nm
        FROM m_driver_kendaraan_doc as a
        INNER JOIN m_driver_kendaraan as b ON a.id_kendaraan = b.kd_kendaraan
        INNER JOIN m_driver as c ON c.kd_driver = b.kd_driver
        WHERE c.kd_driver = ".$iddr;

        $results = DB::select(DB::raw($query));
        if ($results) {
            $image = [];
            foreach ($results as $result) {
                $image[] = $result->pt . $result->nm;
            }
    
            return response()->json([
                'status' => 1,
                'message' => 'Data ditemukan',
                'data' => $image
            ], 200);
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'Data tidak ditemukan',
                'data' => []
            ], 500);
        }
    }
}
