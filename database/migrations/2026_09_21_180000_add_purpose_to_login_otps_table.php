<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_otps', function (Blueprint $table) {
            $table->string('purpose', 20)->default('register')->after('channel');
            $table->index(['user_id', 'purpose', 'channel', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::table('login_otps', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'purpose', 'channel', 'verified_at']);
            $table->dropColumn('purpose');
        });
    }
};
