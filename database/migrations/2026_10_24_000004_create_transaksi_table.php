<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->increments('id_transaksi');
            $table->unsignedInteger('id_grup');
            $table->unsignedInteger('id_penalang');
            $table->unsignedInteger('id_pencatat');
            $table->string('nama_barang', 100);
            $table->decimal('nominal', 10, 2);
            $table->date('tanggal');
            $table->string('foto_bukti', 255)->nullable();

            $table->index(['id_grup', 'tanggal', 'id_transaksi']);
            $table->foreign('id_grup')->references('id_grup')->on('grup')->restrictOnDelete();
            $table->foreign('id_penalang')->references('id_anggota')->on('anggota')->restrictOnDelete();
            $table->foreign('id_pencatat')->references('id_anggota')->on('anggota')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE transaksi ADD CONSTRAINT chk_transaksi_nominal CHECK (nominal > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
