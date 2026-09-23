<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vaccines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('default_interval_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('farmer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('farm_name')->nullable();
            $table->string('phone');
            $table->text('address')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('regency')->nullable();
            $table->string('province')->nullable();
            $table->string('profile_photo')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cattle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmer_profiles')->restrictOnDelete();
            $table->foreignId('breed_id')->constrained('breeds')->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->enum('sex', ['male', 'female']);
            $table->date('birth_date')->nullable();
            $table->boolean('estimated_birth_date')->default(false);
            $table->string('color')->nullable();
            $table->string('origin')->nullable();
            $table->date('entry_date')->nullable();
            $table->string('main_photo')->nullable();
            $table->enum('status', ['active', 'sold', 'dead'])->default('active');
            $table->string('qr_token')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform', 30);
            $table->string('device_name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('cattle');
        Schema::dropIfExists('farmer_profiles');
        Schema::dropIfExists('vaccines');
        Schema::dropIfExists('breeds');
    }
};
