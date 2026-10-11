<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('catalog_code', 50)->nullable()->unique();
            $table->json('import_aliases')->nullable();
        });
        Schema::table('members', fn (Blueprint $table) => $table->json('senayan_data')->nullable());
        Schema::table('book_copies', fn (Blueprint $table) => $table->boolean('import_unavailable')->default(false));
    }

    public function down(): void
    {
        Schema::table('books', fn (Blueprint $table) => $table->dropColumn(['catalog_code', 'import_aliases']));
        Schema::table('members', fn (Blueprint $table) => $table->dropColumn('senayan_data'));
        Schema::table('book_copies', fn (Blueprint $table) => $table->dropColumn('import_unavailable'));
    }
};
