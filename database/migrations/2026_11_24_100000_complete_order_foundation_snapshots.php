<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedInteger('vat_rate_basis_points')->nullable()->after('vat_cents');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('product_code')->nullable()->after('package_id');
            $table->string('currency', 3)->default('EUR')->after('price_cents');
            $table->unsignedInteger('vat_rate_basis_points')->nullable()->after('currency');
            $table->integer('subtotal_cents')->default(0)->after('quantity');
            $table->integer('vat_cents')->default(0)->after('subtotal_cents');
        });

        DB::table('order_items')->where('subtotal_cents', 0)->update([
            'subtotal_cents' => DB::raw('total_cents'),
        ]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn([
                'product_code',
                'currency',
                'vat_rate_basis_points',
                'subtotal_cents',
                'vat_cents',
            ]);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('vat_rate_basis_points');
        });
    }
};
