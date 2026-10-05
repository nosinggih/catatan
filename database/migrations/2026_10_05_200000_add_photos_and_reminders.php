<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('location_name');
        });

        Schema::table('activities', function (Blueprint $table) {
            // When the last "it's time again" push went out for this activity.
            $table->timestamp('reminded_at')->nullable()->after('reminder_interval_days');
        });
    }

    public function down(): void
    {
        Schema::table('entries', fn (Blueprint $table) => $table->dropColumn('photo_path'));
        Schema::table('activities', fn (Blueprint $table) => $table->dropColumn('reminded_at'));
    }
};
