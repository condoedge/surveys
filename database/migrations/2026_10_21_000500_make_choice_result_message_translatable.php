<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A choice's own end message now holds both languages as JSON: a message typed before is kept under the app locale.
     */
    public function up(): void
    {
        DB::table('choices')->whereNotNull('choice_result_message')->orderBy('id')->each(function ($choice) {
            if (!is_array(json_decode($choice->choice_result_message, true))) {
                DB::table('choices')->where('id', $choice->id)
                    ->update(['choice_result_message' => json_encode([config('app.locale') => $choice->choice_result_message])]);
            }
        });
    }

    public function down(): void
    {
        DB::table('choices')->whereNotNull('choice_result_message')->orderBy('id')->each(function ($choice) {
            $translations = json_decode($choice->choice_result_message, true);

            if (is_array($translations)) {
                DB::table('choices')->where('id', $choice->id)
                    ->update(['choice_result_message' => $translations[config('app.locale')] ?? collect($translations)->filter()->first()]);
            }
        });
    }
};
