<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['membership_plan_id']);
        });

        DB::statement('ALTER TABLE members MODIFY membership_plan_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE members MODIFY tanggal_expired DATE NULL');
        DB::statement("ALTER TABLE members MODIFY status ENUM('non_member', 'active', 'inactive', 'expired') NOT NULL DEFAULT 'non_member'");

        Schema::table('members', function (Blueprint $table) {
            $table->foreign('membership_plan_id')->references('id')->on('membership_plans')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['membership_plan_id']);
        });

        DB::statement('ALTER TABLE members MODIFY membership_plan_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE members MODIFY tanggal_expired DATE NOT NULL');
        DB::statement("ALTER TABLE members MODIFY status ENUM('active', 'inactive', 'expired') NOT NULL DEFAULT 'active'");

        Schema::table('members', function (Blueprint $table) {
            $table->foreign('membership_plan_id')->references('id')->on('membership_plans')->cascadeOnDelete();
        });
    }
};
