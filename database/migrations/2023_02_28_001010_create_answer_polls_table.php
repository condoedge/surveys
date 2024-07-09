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
        Schema::create('answer_polls', function (Blueprint $table) {
            
            addMetaData($table);

            $table->foreignId('answer_id')->constrained();
            $table->foreignId('poll_id')->constrained();
            $table->text('answer_text')->nullable();
            $table->tinyInteger('is_open_answer')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answer_polls');
    }
};
