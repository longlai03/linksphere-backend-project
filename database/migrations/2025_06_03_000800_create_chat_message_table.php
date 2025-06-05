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
        Schema::create('chat_message', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('chat')->onDelete('cascade');
            $table->foreignId('attachment_id')->nullable()->constrained('attachment')->onDelete('set null');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['sent', 'delivered', 'read', 'failed'])->default('sent');
            $table->text('content')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_message');
    }
};
