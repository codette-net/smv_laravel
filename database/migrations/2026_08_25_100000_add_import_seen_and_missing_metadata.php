<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imports', function (Blueprint $table): void {
            $table->unsignedInteger('missing_rows')->default(0)->after('failed_rows');
            $table->unsignedInteger('restored_rows')->default(0)->after('missing_rows');
        });

        Schema::table('vacancies', function (Blueprint $table): void {
            $table->timestamp('last_seen_at')->nullable()->after('source_reference');
            $table->foreignId('last_seen_import_id')->nullable()->after('last_seen_at')->constrained('imports')->nullOnDelete();
            $table->timestamp('missing_since')->nullable()->after('last_seen_import_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('last_seen_import_id');
            $table->dropColumn(['last_seen_at', 'missing_since']);
        });

        Schema::table('imports', fn (Blueprint $table) => $table->dropColumn(['missing_rows', 'restored_rows']));
    }
};
