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
        $tablePrefix = config('laravel-crm.db_table_prefix');
        
        // Increase email address column to TEXT to accommodate encrypted values
        DB::statement("ALTER TABLE `{$tablePrefix}emails` MODIFY COLUMN `address` TEXT");
        
        // Increase phone number column to TEXT to accommodate encrypted values
        DB::statement("ALTER TABLE `{$tablePrefix}phones` MODIFY COLUMN `number` TEXT");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tablePrefix = config('laravel-crm.db_table_prefix');
        
        // Revert email address column back to VARCHAR(255)
        DB::statement("ALTER TABLE `{$tablePrefix}emails` MODIFY COLUMN `address` VARCHAR(255)");
        
        // Revert phone number column back to VARCHAR(255)
        DB::statement("ALTER TABLE `{$tablePrefix}phones` MODIFY COLUMN `number` VARCHAR(255)");
    }
};
