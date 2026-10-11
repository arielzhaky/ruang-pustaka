<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->text('login_qr_token')->nullable();
            $table->string('login_qr_hash', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['login_qr_hash']);
            $table->dropColumn(['login_qr_token', 'login_qr_hash']);
        });
    }
};
