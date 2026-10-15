<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imports', fn (Blueprint $table) => $table->unsignedInteger('skipped_rows')->default(0)->after('updated_rows'));
    }

    public function down(): void
    {
        Schema::table('imports', fn (Blueprint $table) => $table->dropColumn('skipped_rows'));
    }
};
