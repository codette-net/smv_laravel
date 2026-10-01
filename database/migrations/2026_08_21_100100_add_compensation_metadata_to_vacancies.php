<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table): void {
            $table->string('salary_currency', 3)->nullable()->after('salary_max');
            $table->string('salary_period')->nullable()->after('salary_currency');
            $table->string('rate_currency', 3)->nullable()->after('rate_max');
            $table->string('rate_period')->nullable()->after('rate_currency');
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table): void {
            $table->dropColumn(['salary_currency', 'salary_period', 'rate_currency', 'rate_period']);
        });
    }
};
