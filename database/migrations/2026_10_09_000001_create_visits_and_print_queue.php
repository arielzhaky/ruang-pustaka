<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('student_name');
            $table->string('member_code');
            $table->string('class_name')->nullable();
            $table->date('visit_date')->index();
            $table->dateTime('visited_at');
            $table->timestamps();
            $table->unique(['member_id', 'visit_date']);
        });
        Schema::create('print_queue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('book_copy_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'member_id']);
            $table->unique(['user_id', 'book_copy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_queue_items');
        Schema::dropIfExists('library_visits');
    }
};
