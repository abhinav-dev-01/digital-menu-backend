<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->integer('preparation_time')->default(15); // in minutes
            $table->boolean('is_veg')->default(true);
            $table->integer('spicy_level')->default(0); // 0 (Mild) to 3 (Extra Spicy)
            $table->integer('calories')->nullable();
            $table->json('ingredients')->nullable();
            $table->json('allergens')->nullable();
            $table->time('available_start_time')->nullable();
            $table->time('available_end_time')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_bestseller')->default(false);
            $table->boolean('is_recommended')->default(false);
            $table->string('availability_schedule_type')->default('manual'); // manual, breakfast, lunch, snacks, dinner, weekend, festival
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
