<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bcs_records', function (Blueprint $table) {
            $table->decimal('weight_kg_snapshot', 8, 2)->nullable()->after('score');
            $table->text('recommendation_summary')->nullable()->after('notes');
            $table->json('recommendations')->nullable()->after('recommendation_summary');
        });
    }

    public function down(): void
    {
        Schema::table('bcs_records', function (Blueprint $table) {
            $table->dropColumn(['weight_kg_snapshot', 'recommendation_summary', 'recommendations']);
        });
    }
};
