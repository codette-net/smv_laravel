<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_apply_clicks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vacancy_id')->constrained()->cascadeOnDelete();
            $table->timestamp('clicked_at')->useCurrent();

            $table->index(['vacancy_id', 'clicked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_apply_clicks');
    }
};
