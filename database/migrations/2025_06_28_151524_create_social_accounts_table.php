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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password')->nullable();
            $table->text('cookie')->nullable();
        
            $table->foreignId('platform_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained()->onDelete('set null');
        
            $table->enum('status', ['active', 'banned', 'suspended', 'pending'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
