<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('restaurant_name');
            $table->string('owner_name');
            $table->string('phone');
            $table->string('email');
            $table->string('city');
            $table->string('selected_plan');
            $table->enum('status', ['Pending', 'Contacted', 'Converted', 'Cancelled'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_enquiries');
    }
};
