<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The contact now holds both languages as JSON: a database that ran the first version still has a varchar(255).
     * Raw statement: ->change() needs doctrine/dbal on the Laravel 10 hosts.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE surveys MODIFY end_contact TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE surveys MODIFY end_contact VARCHAR(255) NULL');
    }
};
