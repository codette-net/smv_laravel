<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('payment_provider', 'provider');
            $table->renameColumn('payment_id', 'provider_payment_id');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('featured_image')->nullable()->after('excerpt');
            $table->longText('content')->change();
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->text('content')->change();
            $table->dropColumn('featured_image');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('provider', 'payment_provider');
            $table->renameColumn('provider_payment_id', 'payment_id');
        });
    }
};
