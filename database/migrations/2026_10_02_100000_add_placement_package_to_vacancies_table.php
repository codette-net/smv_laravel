<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table): void {
            $table->string('placement_package')->nullable()->after('source');
            $table->index('placement_package');
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table): void {
            $table->dropIndex(['placement_package']);
            $table->dropColumn('placement_package');
        });
    }
};
