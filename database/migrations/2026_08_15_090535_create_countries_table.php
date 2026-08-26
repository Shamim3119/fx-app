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
        Schema::create('countries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 45)->nullable();
            $table->boolean('currency_type')->default(1);
            $table->decimal('general_to_master', 12, 8)->default(1.00000000);
            $table->decimal('master_to_general', 12, 8)->default(1.00000000);

            $table->decimal('general_to_secondary', 12, 8)->default(1.00000000);
            $table->decimal('secondary_to_general', 12, 8)->default(1.00000000);
 
            $table->string('currency', 5)->default('TK');
            $table->string('prefix', 10)->nullable();
            $table->string('code', 10)->nullable();
            $table->string('img')->nullable();
            $table->boolean('inactive')->default(0);
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
