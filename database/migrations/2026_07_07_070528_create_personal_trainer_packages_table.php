<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personal_trainer_packages', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedInteger('jumlah_sesi');
            $table->unsignedInteger('masa_berlaku_hari')->nullable();
            $table->decimal('harga', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_trainer_packages');
    }
};
