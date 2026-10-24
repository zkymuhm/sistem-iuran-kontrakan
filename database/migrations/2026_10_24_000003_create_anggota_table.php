<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggota', function (Blueprint $table) {
            $table->increments('id_anggota');
            $table->unsignedInteger('id_grup');
            $table->unsignedInteger('id_pengguna');
            $table->enum('status_aktif', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('bergabung_pada')->useCurrent();

            $table->unique(['id_grup', 'id_pengguna']);
            $table->foreign('id_grup')->references('id_grup')->on('grup')->restrictOnDelete();
            $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota');
    }
};
