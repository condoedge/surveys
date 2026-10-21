<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each answer can say where to go next: a later question or an end (yes, no, warn).
     */
    public function up(): void
    {
        Schema::table('choices', function (Blueprint $table) {
            $table->string('choice_next', 20)->nullable();
            $table->text('choice_result_message')->nullable();
        });

        Schema::table('polls', function (Blueprint $table) {
            $table->string('poll_next_default', 20)->nullable();
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->text('end_yes_message')->nullable();
            $table->text('end_no_message')->nullable();
            $table->text('end_warn_message')->nullable();
            $table->string('end_contact')->nullable();
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->tinyInteger('form_result')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('choices', fn (Blueprint $table) => $table->dropColumn(['choice_next', 'choice_result_message']));
        Schema::table('polls', fn (Blueprint $table) => $table->dropColumn('poll_next_default'));
        Schema::table('surveys', fn (Blueprint $table) => $table->dropColumn(['end_yes_message', 'end_no_message', 'end_warn_message', 'end_contact']));
        Schema::table('answers', fn (Blueprint $table) => $table->dropColumn('form_result'));
    }
};
