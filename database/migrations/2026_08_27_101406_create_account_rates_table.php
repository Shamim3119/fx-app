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
        Schema::create('account_rates', function (Blueprint $table) {
            $table->id();

            $table->unsignedTinyInteger('type_id');

            $table->unsignedInteger('currency_id');
            $table->foreign('currency_id')
                ->references('id')
                ->on('countries')
                ->cascadeOnDelete();
            
            $table->decimal('general_to_master', 12, 8)->default(1.00000000);
            $table->decimal('master_to_general', 12, 8)->default(1.00000000);
            $table->decimal('general_to_secondary', 12, 8)->default(1.00000000);
            $table->decimal('secondary_to_general', 12, 8)->default(1.00000000);

 

            $table->index('type_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_rates');
    }
};
