<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('normalized_name', 80);
            $table->string('icon', 16)->nullable();
            $table->unsignedSmallInteger('reminder_interval_days')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['household_id', 'normalized_name']);
        });

        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('occurred_at');
            $table->string('note', 500)->nullable();
            $table->unsignedInteger('cost')->nullable();
            $table->string('location_name', 120)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['activity_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
        Schema::dropIfExists('activities');
    }
};
