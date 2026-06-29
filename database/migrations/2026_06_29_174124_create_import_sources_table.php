<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')
                ->default(\App\Enums\ImportType::xml->value);
            $table->string('endpoint_url')->nullable();
            $table->string('auth_type')->nullable();
            $table->json('credentials')->nullable();
            $table->json('default_mapping')->nullable();
            $table->boolean('is_active')->default(false);
            $table->datetime('last_imported_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_sources');
    }
};
