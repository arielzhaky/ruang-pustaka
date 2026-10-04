<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_items', function (Blueprint $table) {
            // Catatan lama tidak otomatis dianggap belum dibayar.
            $table->string('billing_status', 20)->default('unverified');
        });
        Schema::create('fine_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_item_id')->unique()->constrained()->restrictOnDelete();
            $table->uuid('receipt_code')->unique();
            $table->unsignedBigInteger('amount');
            $table->string('method', 20);
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('paid_at');
            $table->string('notes', 500)->nullable();
            $table->timestamps();
        });
        Schema::create('loan_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->date('original_due_date');
            $table->date('requested_due_date');
            $table->string('reason', 500)->nullable();
            $table->string('decision_note', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('book_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_copy_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('waiting');
            // NULL untuk riwayat; unique menjaga satu reservasi aktif per siswa/judul.
            $table->string('active_key')->nullable()->unique();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['book_id', 'status', 'id']);
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name');
            $table->string('action');
            $table->text('targets')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('book_reservations');
        Schema::dropIfExists('loan_renewals');
        Schema::dropIfExists('fine_payments');
        Schema::table('loan_items', fn (Blueprint $table) => $table->dropColumn('billing_status'));
    }
};
