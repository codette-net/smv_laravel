<?php

use App\Enums\ImportFormat;
use App\Enums\ImportTransport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('import_sources')->exists()) {
            throw new RuntimeException('Cannot add required ImportSource ownership while existing sources have no owning Company. Assign each source to its feed-owner Company before running this migration.');
        }

        Schema::table('import_sources', function (Blueprint $table): void {
            $table->foreignId('company_id')->after('id')->constrained()->restrictOnDelete();
            $table->string('transport')->default(ImportTransport::Upload->value)->after('type');
            $table->string('format')->default(ImportFormat::Xml->value)->after('transport');
            $table->json('configuration')->nullable()->after('credentials');
            $table->string('record_path')->nullable()->after('configuration');
            $table->json('selection_rules')->nullable()->after('record_path');
            $table->timestamp('approved_at')->nullable()->after('is_active');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->index(['is_active', 'approved_at'], 'import_sources_active_approved_index');
        });
    }

    public function down(): void
    {
        Schema::table('import_sources', function (Blueprint $table): void {
            $table->dropIndex('import_sources_active_approved_index');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approved_at', 'selection_rules', 'record_path', 'configuration', 'format', 'transport']);
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
