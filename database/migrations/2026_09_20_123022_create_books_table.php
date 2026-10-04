<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();

            // Menghubungkan buku ke categories.id.
            // Kategori yang masih digunakan buku tidak boleh dihapus.
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->string('isbn', 20)->nullable()->unique();
            $table->string('title');
            $table->string('author');
            $table->string('publisher')->nullable();
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();

            // Diisi ketika judul buku diarsipkan.
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};