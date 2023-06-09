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
        ->join('m_barang', 'm_barang.id', '=', 'm_barang_satuan.barang_id')->where('t_penjualan_detail.no_transaksi', $resi)->select('m_barang.nama','t_penjualan_detail.qty','t_penjualan_detail.harga_jual')->first();

        if (!empty($query)) {
            return response()->json([
                'status' => 1,
                'message' => 'Data ditemuka',
                'menu' => $query->nama,
                'haraga' => $query->harga_jual,
                'qty' => $query->qty
            ]);
        }else{
            return response()->json([
                'status' => 0,
                'message' => 'Data tidak ditemukann',
                'data' => []
            ]);
        }

    }
}
