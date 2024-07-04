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
        Schema::create('surveys', function (Blueprint $table) {
            
            addMetaData($table);

            $table->foreignId('team_id')->nullable()->constrained();
            $table->nullableMorphs('surveyable');

            $table->tinyInteger('survey_type')->nullable();
            $table->string('name_sv');
            $table->string('subtitle_sv')->nullable();
            $table->string('description_sv')->nullable();
            $table->string('qrcode_sv')->nullable();

            $table->tinyInteger('one_page')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
