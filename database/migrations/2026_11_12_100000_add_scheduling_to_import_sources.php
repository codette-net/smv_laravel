<?php

use App\Enums\ImportScheduleFrequency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_sources', function (Blueprint $table): void {
            $table->boolean('automatic_import_enabled')->default(false)->after('is_active');
            $table->string('schedule_frequency')->default(ImportScheduleFrequency::Hourly->value)->after('automatic_import_enabled');
            $table->timestamp('last_scheduled_at')->nullable()->after('last_imported_at');
            $table->index(
                ['automatic_import_enabled', 'is_active', 'schedule_frequency'],
                'import_sources_automatic_schedule_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('import_sources', function (Blueprint $table): void {
            $table->dropIndex('import_sources_automatic_schedule_index');
            $table->dropColumn(['automatic_import_enabled', 'schedule_frequency', 'last_scheduled_at']);
        });
    }
};
