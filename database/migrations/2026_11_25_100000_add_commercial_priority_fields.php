<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->smallInteger('commercial_priority')->default(0)->after('is_featured');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->smallInteger('commercial_priority')->default(0)->after('is_featured');
            $table->index('commercial_priority', 'companies_commercial_priority_index');
        });

        Schema::table('vacancies', function (Blueprint $table): void {
            $table->smallInteger('commercial_priority_adjustment')->default(0)->after('is_featured');
            $table->index('commercial_priority_adjustment', 'vacancies_commercial_priority_adjustment_index');
        });

        DB::table('packages')->where('slug', 'standard')->update(['commercial_priority' => 0]);
        DB::table('packages')->where('slug', 'superior')->update(['commercial_priority' => 20]);
        DB::table('packages')->where('slug', 'custom')->update(['commercial_priority' => 0]);
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table): void {
            $table->dropIndex('vacancies_commercial_priority_adjustment_index');
            $table->dropColumn('commercial_priority_adjustment');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex('companies_commercial_priority_index');
            $table->dropColumn('commercial_priority');
        });

        Schema::table('packages', function (Blueprint $table): void {
            $table->dropColumn('commercial_priority');
        });
    }
};
