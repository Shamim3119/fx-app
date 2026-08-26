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
        Schema::create('account_balances', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('balance', 16, 8)->default(0);    

            $table->unsignedInteger('currency_id');
            $table->foreign('currency_id')
                ->references('id')
                ->on('countries')
                ->cascadeOnUpdate()
                ->restrictOnDelete(); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('account_balances');
        Schema::enableForeignKeyConstraints();
    }
};
