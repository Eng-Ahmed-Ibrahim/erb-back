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
        Schema::create('mc_membership_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('mc_subscriptions')->onDelete('cascade');
            $table->string('card_uid', 20)->unique(); // NFC UID in hex format
            $table->string('serial_id', 32)->nullable()->comment('Card serial ID (UID) read from NFC reader');
            $table->string('card_token', 64)->nullable()->comment('Unique token/hash for the subscription');
            $table->string('card_token_hex', 32)->nullable()->comment('Token in HEX format (16 bytes) written to card');
            $table->timestamp('printed_at')->nullable();
            $table->timestamp('encoded_at')->nullable();
            $table->date('expiry_date');
            $table->enum('status', ['active', 'revoked', 'expired'])->default('active');
            $table->json('encoded_data')->nullable(); // Encrypted payload stored on card
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['subscription_id']);
            $table->index(['card_uid']);
            $table->index(['serial_id']);
            $table->index(['card_token']);
            $table->index(['card_token_hex']);
            $table->index(['status']);
            $table->index(['expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mc_membership_cards');
    }
};

