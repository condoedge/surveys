<?php

use Condoedge\Surveys\Models\Condition;
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
        Schema::create('conditions', function (Blueprint $table) {
            
            addMetaData($table);

            $table->foreignId('poll_id')->constrained();
            $table->tinyInteger('condition_type')->default(Condition::TYPE_IS_EQUAL_TO);
            $table->foreignId('condition_poll_id')->constrained('polls');
            $table->foreignId('condition_choice_id')->nullable()->constrained('choices');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conditions');
    }
};
