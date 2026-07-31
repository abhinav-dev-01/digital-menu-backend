<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            if (!Schema::hasColumn('menu_items', 'food_type')) {
                $table->string('food_type')->default('Veg')->after('is_veg');
            }
            if (!Schema::hasColumn('menu_items', 'spice_level')) {
                $table->string('spice_level')->default('Mild')->after('spicy_level');
            }
            if (!Schema::hasColumn('menu_items', 'badges')) {
                $table->json('badges')->nullable()->after('is_recommended');
            }
            if (!Schema::hasColumn('menu_items', 'status')) {
                $table->string('status')->default('Active')->after('is_available');
            }
            if (!Schema::hasColumn('menu_items', 'sizes')) {
                $table->json('sizes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('menu_items', 'addons')) {
                $table->json('addons')->nullable()->after('sizes');
            }
            if (!Schema::hasColumn('menu_items', 'always_available')) {
                $table->boolean('always_available')->default(true)->after('available_end_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn(['food_type', 'spice_level', 'badges', 'status', 'sizes', 'addons', 'always_available']);
        });
    }
};
