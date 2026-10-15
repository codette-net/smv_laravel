<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_source_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['import_source_id', 'name']);
            $table->index(['import_source_id', 'is_default']);
        });

        Schema::create('import_mapping_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_mapping_id')->constrained()->cascadeOnDelete();
            $table->string('destination_key');
            $table->string('operation');
            $table->json('source_paths')->nullable();
            $table->json('configuration')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['import_mapping_id', 'destination_key']);
            $table->index(['import_mapping_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_mapping_fields');
        Schema::dropIfExists('import_mappings');
    }
};
