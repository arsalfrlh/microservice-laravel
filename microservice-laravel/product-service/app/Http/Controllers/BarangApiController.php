<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BarangApiController extends Controller
{
    public function index(){
        $data = Barang::all();
        return response()->json(['message' => "Menampilkan semua data barang", 'success' => true, 'data' => $data]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(),[
            'gambar' => 'required|image|mimes:jpeg,jpg,png',
            'nama_barang' => 'required',
            'merk' => 'required',
            'stok' => 'required|numeric',
            'harga' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json(['message' => $validator->errors()->all(), 'success' => false]);
        }

        if($request->hasFile('gambar')){
            $gambar = $request->file('gambar');
            $nmgambar = time() . '_' . $gambar->getClientOriginalName();
            $gambar->move(public_path('images'), $nmgambar);
        }else{
            $nmgambar = null;
        }

        $data = Barang::create([
            'gambar' => $nmgambar,
            'nama_barang' => $request->nama_barang,
            'merk' => $request->merk,
            'stok' => $request->stok,
            'harga' => $request->harga
        ]);

        return response()->json(['message' => "Barang berhasil ditambahkan", 'success' => true, 'data' => $data]);
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(),[
            'id_barang' => 'required|numeric',
            'gambar' => 'image|mimes:jpeg,jpg,png',
            'nama_barang' => 'required',
            'merk' => 'required',
            'stok' => 'required|numeric',
            'harga' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json(['message' => $validator->errors()->all(), 'success' => false]);
        }

        $barang = Barang::find($request->id_barang);
        if($request->hasFile('gambar')){
            if(file_exists(public_path('images/'.$barang->gambar))){
                unlink(public_path('images/'.$barang->gambar));
            }

            $gambar = $request->file('gambar');
            $nmgambar = time() . '_' . $gambar->getClientOriginalName();
            $gambar->move(public_path('images'), $nmgambar);
        }else{
            $nmgambar = $barang->gambar;
        }

        $data = Barang::where('id', $request->id_barang)->update([
            'gambar' => $nmgambar,
            'nama_barang' => $request->nama_barang,
            'merk' => $request->merk,
            'stok' => $request->stok,
            'harga' => $request->harga
        ]);

        return response()->json(['message' => "Barang berhasil diupdate", 'success' => true, 'data' => $data]);
    }

    public function destroy($id){
        $barang = Barang::findOrFail($id);
        if(file_exists(public_path('images/'.$barang->gambar))){
            unlink(public_path('images/'.$barang->gambar));
        }

        $barang->delete();
        return response()->json(['message' => "Barang telah dihapus", 'success' => true, 'data' => $barang]);
    }
}
