<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_pembagian', function (Blueprint $table) {
            $table->increments('id_detail');
            $table->unsignedInteger('id_transaksi');
            $table->unsignedInteger('id_anggota');
            $table->decimal('nominal_bagian', 10, 2);
            $table->enum('status_bayar', ['belum', 'menunggu_konfirmasi', 'lunas'])->default('belum');
            $table->timestamp('ditandai_pada')->nullable();
            $table->timestamp('dikonfirmasi_pada')->nullable();

            $table->unique(['id_transaksi', 'id_anggota']);
            $table->foreign('id_transaksi')->references('id_transaksi')->on('transaksi')->cascadeOnDelete();
            $table->foreign('id_anggota')->references('id_anggota')->on('anggota')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE detail_pembagian ADD CONSTRAINT chk_detail_nominal CHECK (nominal_bagian > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pembagian');
    }
};
