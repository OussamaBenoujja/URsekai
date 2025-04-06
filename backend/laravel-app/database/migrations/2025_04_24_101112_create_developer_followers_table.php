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
        Schema::create('developer_followers', function (Blueprint $table) {
            $table->id('follower_id');
            $table->unsignedBigInteger('developer_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('notify_new_games')->default(true);
            $table->boolean('notify_updates')->default(true);
            $table->timestamps();

            // Foreign keys
            $table->foreign('developer_id')->references('developer_id')->on('developers')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate follows
            $table->unique(['developer_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('developer_followers');
    }
};
