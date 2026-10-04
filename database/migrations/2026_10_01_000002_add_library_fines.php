<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->unsignedInteger('replacement_price')->nullable();
        });
        Schema::table('loan_items', function (Blueprint $table) {
            $table->unsignedInteger('fine_daily_rate')->default(2000);
            // Null berarti transaksi lama sudah ditutup sebelum pencatatan denda.
            $table->unsignedBigInteger('fine_amount')->nullable();
            $table->unsignedInteger('replacement_amount')->default(0);
            $table->enum('return_condition', ['baik', 'rusak', 'hilang'])->nullable()->change();
        });
    }

    public function down(): void
    {
        // Biarkan enum menerima 'hilang' agar rollback tidak merusak riwayat kehilangan.
        Schema::table('loan_items', function (Blueprint $table) {
            $table->dropColumn(['fine_daily_rate', 'fine_amount', 'replacement_amount']);
        });
        Schema::table('books', fn (Blueprint $table) => $table->dropColumn('replacement_price'));
    }
};
