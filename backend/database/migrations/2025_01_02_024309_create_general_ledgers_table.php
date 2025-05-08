<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('general_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id');
            $table->foreign('import_id')->references('id')->on('general_ledger_imports')->onDelete('cascade');
            $table->date('transaction_date');
            $table->integer('periode')->comment('Ym format, ex: 202501');
            $table->string('reference');
            $table->string('reference_no');
            $table->string('department');
            $table->unsignedBigInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->text('description');
            $table->tinyInteger('transaction_type')->default(1)->comment('1 for debit, 2 for credit');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_ledgers');
    }
};
