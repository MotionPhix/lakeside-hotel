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
        Schema::table('dining_venues', function (Blueprint $table) {
            // Per section notes, keyed by menu section, e.g.
            // {"sandwiches": "All served with chips."}. A printed menu states these
            // once under the heading rather than repeating them on every dish.
            $table->json('section_notes')->nullable()->after('opening_hours');

            // A note that applies to the whole menu, e.g. "Prices are tax
            // inclusive." Printed once at the top of the card.
            $table->string('menu_note')->nullable()->after('section_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dining_venues', function (Blueprint $table) {
            $table->dropColumn(['section_notes', 'menu_note']);
        });
    }
};
