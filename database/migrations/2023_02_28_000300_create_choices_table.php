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
        Schema::create('choices', function (Blueprint $table) {
            
            addMetaData($table);

            $table->foreignId('poll_id')->constrained();

            $table->string('choice_content')->nullable();
            $table->decimal('choice_max_quantity', 10, 2)->nullable();
            $table->decimal('choice_amount', 10, 2)->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('choices');
    }
};
