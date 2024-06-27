<?php

use Condoedge\Surveys\Models\PollSection;
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
        Schema::create('poll_sections', function (Blueprint $table) {
            
            addMetaData($table);

            $table->foreignId('survey_id')->constrained();

            $table->tinyInteger('type_ps')->default(PollSection::PS_SINGLE_TYPE);
            $table->unsignedInteger('order')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_sections');
    }
};
