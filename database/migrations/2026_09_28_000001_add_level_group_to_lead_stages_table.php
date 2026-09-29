<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_stages', function (Blueprint $table) {
            $table->string('level_group', 8)->nullable()->after('slug');
            $table->index('level_group');
        });
    }

    public function down(): void
    {
        Schema::table('lead_stages', function (Blueprint $table) {
            $table->dropIndex(['level_group']);
            $table->dropColumn('level_group');
        });
    }
};
