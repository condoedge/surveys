<?php

use Condoedge\Surveys\Models\PollTypeEnum;

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
        Schema::table('polls', function (Blueprint $table) {

            $table->foreignId('poll_id')->nullable()->constrained();
            $table->nullableMorphs('pollable');
            $table->tinyInteger('poll_occurence')->nullable();

            $table->tinyInteger('link_to_pollable_units')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
