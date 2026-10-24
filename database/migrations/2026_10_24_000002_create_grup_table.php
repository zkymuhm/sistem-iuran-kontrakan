<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grup', function (Blueprint $table) {
            $table->increments('id_grup');
            $table->string('nama_grup', 100);
            $table->char('kode_invitasi', 6)->unique();
            $table->unsignedInteger('id_pembuat');
            $table->enum('status_aktif', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('dibuat_pada')->useCurrent();

            $table->foreign('id_pembuat')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grup');
    }
};
