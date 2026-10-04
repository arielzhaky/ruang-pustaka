<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();

            // Setiap eksemplar terhubung ke satu judul buku.
            $table->foreignId('book_id')
                ->constrained('books')
                ->restrictOnDelete();

            // Kode unik untuk setiap buku fisik.
            $table->string('inventory_code', 50)->unique();

            $table->string('shelf_location', 100)->nullable();

            $table->enum('condition', [
                'baik',
                'rusak',
                'hilang',
            ])->default('baik');

            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};