<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // String menjaga angka nol di awal NIS.
            $table->string('nis', 30)->nullable()->unique();

            $table->foreignId('school_class_id')
                ->nullable()
                ->constrained('school_classes')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_class_id');
            $table->dropUnique(['nis']);
            $table->dropColumn('nis');
        });
    }
};