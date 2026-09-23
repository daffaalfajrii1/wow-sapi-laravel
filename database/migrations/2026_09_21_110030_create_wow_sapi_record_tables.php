<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cattle_id')->constrained('cattle')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['weight', 'lumpy', 'combined', 'bcs']);
            $table->string('image_path');
            $table->unsignedInteger('cow_count')->nullable();
            $table->decimal('detector_confidence', 8, 4)->nullable();
            $table->decimal('estimated_weight_kg', 8, 2)->nullable();
            $table->boolean('lumpy_detected')->nullable();
            $table->string('lumpy_label')->nullable();
            $table->decimal('lumpy_probability', 8, 4)->nullable();
            $table->decimal('bcs_score', 2, 1)->nullable();
            $table->string('bcs_category')->nullable();
            $table->json('raw_response')->nullable();
            $table->enum('status', ['success', 'partial', 'failed']);
            $table->timestamp('examined_at');
            $table->timestamps();
        });

        Schema::create('weight_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cattle_id')->constrained('cattle')->cascadeOnDelete();
            $table->foreignId('ai_examination_id')->nullable()->constrained('ai_examinations')->nullOnDelete();
            $table->enum('source', ['ai', 'manual']);
            $table->decimal('weight_kg', 8, 2);
            $table->timestamp('measured_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('bcs_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cattle_id')->constrained('cattle')->cascadeOnDelete();
            $table->decimal('score', 2, 1);
            $table->string('category');
            $table->string('image_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('assessed_at');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ai_examination_id')->nullable()->constrained('ai_examinations')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cattle_id')->constrained('cattle')->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('symptoms')->nullable();
            $table->text('treatment')->nullable();
            $table->string('medicine')->nullable();
            $table->string('veterinarian')->nullable();
            $table->timestamp('occurred_at');
            $table->string('status')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('vaccination_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cattle_id')->constrained('cattle')->cascadeOnDelete();
            $table->foreignId('vaccine_id')->constrained('vaccines')->restrictOnDelete();
            $table->date('scheduled_date');
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'done', 'missed', 'cancelled'])->default('scheduled');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('vaccination_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cattle_id')->constrained('cattle')->cascadeOnDelete();
            $table->foreignId('vaccine_id')->constrained('vaccines')->restrictOnDelete();
            $table->foreignId('vaccination_schedule_id')->nullable()->constrained('vaccination_schedules')->nullOnDelete();
            $table->timestamp('administered_at');
            $table->string('batch_no')->nullable();
            $table->string('dose')->nullable();
            $table->string('officer')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('reproduction_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cattle_id')->constrained('cattle')->cascadeOnDelete();
            $table->string('type');
            $table->date('event_date');
            $table->string('partner_code')->nullable();
            $table->string('inseminator')->nullable();
            $table->string('pregnancy_status')->nullable();
            $table->date('expected_birth_date')->nullable();
            $table->date('actual_birth_date')->nullable();
            $table->unsignedInteger('calf_count')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('feed_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cattle_id')->constrained('cattle')->cascadeOnDelete();
            $table->string('feed_name');
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->timestamp('fed_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('mortality_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cattle_id')->unique()->constrained('cattle')->cascadeOnDelete();
            $table->timestamp('died_at');
            $table->string('suspected_cause')->nullable();
            $table->string('confirmed_cause')->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment')->nullable();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('vaccine_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vaccination_schedule_id')->constrained('vaccination_schedules')->cascadeOnDelete();
            $table->string('reminder_type', 10);
            $table->date('scheduled_date');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['vaccination_schedule_id', 'reminder_type', 'scheduled_date'], 'vaccine_reminder_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccine_reminder_logs');
        Schema::dropIfExists('mortality_records');
        Schema::dropIfExists('feed_records');
        Schema::dropIfExists('reproduction_records');
        Schema::dropIfExists('vaccination_records');
        Schema::dropIfExists('vaccination_schedules');
        Schema::dropIfExists('health_records');
        Schema::dropIfExists('bcs_records');
        Schema::dropIfExists('weight_records');
        Schema::dropIfExists('ai_examinations');
    }
};
