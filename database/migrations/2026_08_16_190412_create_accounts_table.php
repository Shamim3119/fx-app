<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {

            $table->id();

            /*
             * 1 = Company
             * 2 = Vendor
             * 3 = Customer
             */
            $table->unsignedTinyInteger('type_id');

            $table->string('name');

            $table->text('address')->nullable();

            $table->string('phone', 30)->nullable();

            $table->string('email')->nullable();

            $table->string('website')->nullable();

            $table->string('logo')->nullable();

            $table->timestamps();

            $table->index('type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};