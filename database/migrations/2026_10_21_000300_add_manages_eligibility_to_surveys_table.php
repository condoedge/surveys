<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Turned on per survey: its answers' arrows lead to questions or to an end, in place of display conditions.
     */
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->tinyInteger('manages_eligibility')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('surveys', fn (Blueprint $table) => $table->dropColumn('manages_eligibility'));
    }
};
