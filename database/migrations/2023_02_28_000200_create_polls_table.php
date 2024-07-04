<?php

use Condoedge\Surveys\Models\Poll;

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
        Schema::create('polls', function (Blueprint $table) {
            
            addMetaData($table);

            $table->foreignId('survey_id')->constrained();
            $table->foreignId('poll_section_id')->constrained();

            $table->tinyInteger('type_po')->default(Poll::PO_TYPE_TEXT);
            $table->tinyInteger('choices_type')->nullable();
            $table->tinyInteger('text_type')->nullable();
            $table->tinyInteger('quantity_type')->nullable();

            $table->text('body_po')->nullable();
            $table->text('explanation_po')->nullable();

            $table->tinyInteger('ask_question_once')->nullable();
            $table->tinyInteger('required_po')->nullable();

            $table->tinyInteger('position_po')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polls');
    }
};
