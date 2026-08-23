<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_taxonomy_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_source_id')->constrained()->cascadeOnDelete();
            $table->string('category_type');
            $table->string('source_key');
            $table->string('source_value');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['import_source_id', 'category_type', 'source_key'], 'import_taxonomy_source_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_taxonomy_mappings');
    }
};
