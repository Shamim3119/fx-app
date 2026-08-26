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
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tan_date')->nullable();
            $table->string('remarks', 255)->nullable();
            $table->unsignedInteger('type_id')->nullable();
            
            $table->unsignedInteger('dr_account')->nullable();
            $table->unsignedInteger('cr_account')->nullable();
            $table->unsignedInteger('dr_sub_account')->nullable();
            $table->unsignedInteger('cr_sub_account')->nullable();
            
            $table->decimal('dr_amount', 16, 8)->nullable();
            $table->decimal('cr_amount', 16, 8)->nullable();
            $table->decimal('dr_balance', 16, 8)->default(0);  
            $table->decimal('cr_balance', 16, 8)->default(0);  
            
            $table->unsignedInteger('transaction_currency')->nullable();
            
            $table->decimal('dr_master_rate', 10, 8)->nullable();
            $table->decimal('cr_master_rate', 10, 8)->nullable();
            $table->decimal('dr_master_amount', 16, 8)->nullable();
            $table->decimal('cr_master_amount', 16, 8)->nullable();
            $table->decimal('dr_master_balance', 16, 8)->default(0);  
            $table->decimal('cr_master_balance', 16, 8)->default(0);  
            $table->unsignedInteger('master_currency')->nullable();
            $table->decimal('master_balance_profit', 16, 8)->nullable();
            
            $table->decimal('dr_secondary_rate', 10, 8)->nullable();
            $table->decimal('cr_secondary_rate', 10, 8)->nullable();
            $table->decimal('dr_secondary_amount', 16, 8)->nullable();
            $table->decimal('cr_secondary_amount', 16, 8)->nullable();
            $table->decimal('dr_secondary_balance', 16, 8)->default(0);  
            $table->decimal('cr_secondary_balance', 16, 8)->default(0);  
            $table->unsignedInteger('secondary_currency')->nullable();
            $table->decimal('secondary_balance_profit', 16, 8)->nullable();
            $table->timestamps();
        });
    }

 
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
