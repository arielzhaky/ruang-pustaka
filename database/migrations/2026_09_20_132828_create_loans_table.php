<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_code', 50)->unique();

            // Anggota yang meminjam.
            $table->foreignId('member_id')
                ->constrained('members')
                ->restrictOnDelete();

            // Petugas yang mencatat peminjaman.
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('borrowed_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};