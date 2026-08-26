<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subsidiary_accounts', function (Blueprint $table) {

            $table->id();

            /*
             * Main Account
             *
             * accounts.id is BIGINT UNSIGNED
             */
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();


            /*
             * Account Name
             */
            $table->string('name');


            /*
             * 1 = Cash
             * 2 = Bank
             */
            $table->unsignedTinyInteger('account_type');

            /*
             * 1 = Company Account
             * 2 = Vendor Account
             * 3 = Customer Account
             */
            $table->unsignedTinyInteger('type_id')
                ->default(1);


            $table->timestamps();


            /*
             * Indexes
             */
            $table->index('account_type');
            $table->index('type_id');


 
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('subsidiary_accounts');
    }
};