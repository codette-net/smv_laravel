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
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('type')->default(\App\Enums\ImportType::xml->value);
            $table->string('filename')->nullable();
            $table->string('status')->default(\App\Enums\ImportStatus::Pending->value);
            $table->integer('total_rows')->default(0);
            $table->integer('updated')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->json('mapping')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('finished_at')->nullable();
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
