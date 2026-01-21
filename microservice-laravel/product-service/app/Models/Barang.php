<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = "barang";
    protected $fillable = ['gambar','nama_barang','merk','stok','harga'];
}
