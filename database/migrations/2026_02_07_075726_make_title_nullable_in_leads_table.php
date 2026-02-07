<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = config('laravel-crm.db_table_prefix').'leads';
        
        // Use raw SQL to modify column without requiring doctrine/dbal
        DB::statement("ALTER TABLE `{$tableName}` MODIFY COLUMN `title` VARCHAR(255) NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('laravel-crm.db_table_prefix').'leads';
        
        // Use raw SQL to modify column back to NOT NULL
        DB::statement("ALTER TABLE `{$tableName}` MODIFY COLUMN `title` VARCHAR(255) NOT NULL");
    }
};
