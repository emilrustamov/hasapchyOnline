<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_type');
            $table->boolean('is_supplier')->default(false);
            $table->boolean('is_conflict')->default(false);
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->text('address')->nullable();
            $table->text('note')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
    }
};
