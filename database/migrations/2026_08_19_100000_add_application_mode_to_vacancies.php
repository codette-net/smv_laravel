<?php

use App\Enums\ApplicationMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table): void {
            $table->string('application_mode')->default(ApplicationMode::Internal->value)->after('application_url');
            $table->index('application_mode');
        });

        DB::table('vacancies')->whereNotNull('application_url')->update(['application_mode' => ApplicationMode::External->value]);
        DB::table('vacancies')->whereNull('application_url')->whereNotNull('application_email')->update(['application_mode' => ApplicationMode::Email->value]);
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table): void {
            $table->dropIndex(['application_mode']);
            $table->dropColumn('application_mode');
        });
    }
};
