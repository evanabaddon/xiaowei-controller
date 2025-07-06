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
        Schema::table('content_tasks', function (Blueprint $table) {
            // Hapus foreign key dan kolom lama jika ada
            if (Schema::hasColumn('content_tasks', 'social_account_id')) {
                $table->dropForeign(['social_account_id']); // Jika ada foreign key
                $table->dropColumn('social_account_id');
            }
            // Tambahkan kolom json baru
            $table->json('social_account_ids')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_tasks', function (Blueprint $table) {
            $table->dropColumn('social_account_ids');
            $table->unsignedBigInteger('social_account_id')->nullable()->after('id');
        });
    }
};
