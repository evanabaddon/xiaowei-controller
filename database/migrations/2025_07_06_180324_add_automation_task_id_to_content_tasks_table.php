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
            $table->unsignedBigInteger('automation_task_id')->nullable()->after('social_account_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_tasks', function (Blueprint $table) {
            $table->dropColumn('automation_task_id');
        });
    }
};
