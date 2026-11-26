<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'ig')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('ig', 255)->nullable()->after('website');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'ig')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('ig');
            });
        }
    }
};
