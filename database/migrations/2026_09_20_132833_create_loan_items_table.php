<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_id')
                ->constrained('loans')
                ->restrictOnDelete();

            $table->foreignId('book_copy_id')
                ->constrained('book_copies')
                ->restrictOnDelete();

            $table->date('due_date');

            // Kosong selama buku belum dikembalikan.
            $table->dateTime('returned_at')->nullable();

            // Petugas yang menerima pengembalian.
            $table->foreignId('returned_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->enum('return_condition', ['baik', 'rusak'])
                ->nullable();

            $table->text('return_notes')->nullable();
            $table->timestamps();

            // Eksemplar yang sama tidak boleh tercatat dua kali
            // dalam satu transaksi peminjaman.
            $table->unique(['loan_id', 'book_copy_id']);

            $table->index(['book_copy_id', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_items');
    }
};