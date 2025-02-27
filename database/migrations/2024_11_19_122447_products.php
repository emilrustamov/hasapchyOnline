<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->unique();
            $table->string('image')->nullable();
            $table->boolean('type')->default('1');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('status_id')->constrained('product_statuses')->onDelete('cascade');
            $table->string('barcode')->unique()->nullable();
            $table->boolean('is_serialized')->default(false);
            $table->foreignId('unit_id')->default(null)->nullable()->constrained('units')->nullOnDelete();
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
