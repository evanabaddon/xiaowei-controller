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
        Schema::table('automation_tasks', function (Blueprint $table) {
            $table->timestamp('last_dispatched_at')->nullable()->after('mode');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automation_tasks', function (Blueprint $table) {
            //
        });
    }
};
