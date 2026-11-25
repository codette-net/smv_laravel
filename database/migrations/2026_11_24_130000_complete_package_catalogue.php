<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->json('features')->nullable()->after('description');
            $table->string('package_type')->default('placement')->after('slug');
            $table->unsignedSmallInteger('placement_quantity')->default(1)->after('vacancy_duration_days');
            $table->boolean('is_quote_only')->default(false)->after('placement_quantity');
            $table->boolean('checkout_eligible')->default(false)->after('is_quote_only');
            $table->boolean('is_featured')->default(false)->after('checkout_eligible');
            $table->unsignedSmallInteger('vat_rate_basis_points')->nullable()->after('currency');
            $table->date('valid_from')->nullable()->after('vat_rate_basis_points');
            $table->date('valid_until')->nullable()->after('valid_from');
            $table->string('cta_label')->nullable()->after('valid_until');
            $table->text('operational_notes')->nullable()->after('cta_label');
            $table->index(['is_active', 'sort_order'], 'packages_public_catalogue_index');
            $table->index(['checkout_eligible', 'valid_until'], 'packages_checkout_validity_index');
        });

        Schema::table('packages', function (Blueprint $table): void {
            $table->string('vacancy_duration_days')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->dropIndex('packages_public_catalogue_index');
            $table->dropIndex('packages_checkout_validity_index');
            $table->dropColumn([
                'features',
                'package_type',
                'placement_quantity',
                'is_quote_only',
                'checkout_eligible',
                'is_featured',
                'vat_rate_basis_points',
                'valid_from',
                'valid_until',
                'cta_label',
                'operational_notes',
            ]);
        });

        DB::table('packages')
            ->whereNull('vacancy_duration_days')
            ->update(['vacancy_duration_days' => '30']);

        Schema::table('packages', function (Blueprint $table): void {
            $table->string('vacancy_duration_days')->nullable(false)->default('30')->change();
        });
    }
};
