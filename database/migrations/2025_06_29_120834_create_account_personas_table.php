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
        Schema::create('account_personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')
                    ->constrained()
                    ->cascadeOnDelete();
            $table->enum('age_range', ['18-25', '26-35', '36-45', '45+']);
            $table->enum('political_leaning', [
                'pro_government', 
                'opposition', 
                'neutral', 
                'apolitical'
            ])->default('neutral');
            $table->json('interests')->nullable();
            $table->string('content_tone')->default('casual');
            $table->text('persona_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_personas');
    }
};
