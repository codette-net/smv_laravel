<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceForeign('companies', 'user_id', 'users', 'null');
        $this->replaceForeign('vacancies', 'company_id', 'companies', 'restrict');
        $this->replaceForeign('applications', 'vacancy_id', 'vacancies', 'restrict');
        $this->replaceForeign('import_logs', 'import_id', 'imports', 'restrict');
        $this->replaceForeign('order_items', 'order_id', 'orders', 'restrict');
        $this->replaceForeign('order_items', 'package_id', 'packages', 'null');
        $this->replaceForeign('order_items', 'vacancy_id', 'vacancies', 'null');
        $this->replaceForeign('payments', 'order_id', 'orders', 'restrict');

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
        $this->replaceForeign('orders', 'user_id', 'users', 'null');
        $this->replaceForeign('orders', 'company_id', 'companies', 'null');

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('author_id')->nullable()->change();
        });
        $this->replaceForeign('blog_posts', 'author_id', 'users', 'null');
    }

    public function down(): void
    {
        $incompatibleNulls = collect([
            'blog_posts.author_id' => DB::table('blog_posts')->whereNull('author_id')->exists(),
            'orders.user_id' => DB::table('orders')->whereNull('user_id')->exists(),
            'orders.company_id' => DB::table('orders')->whereNull('company_id')->exists(),
        ])->filter()->keys();

        if ($incompatibleNulls->isNotEmpty()) {
            throw new RuntimeException(sprintf(
                'Cannot roll back historical record protection: null values in %s cannot be restored to non-nullable columns. No historical records were changed.',
                $incompatibleNulls->implode(', ')
            ));
        }

        $this->replaceForeign('blog_posts', 'author_id', 'users', 'cascade');
        $this->replaceForeign('orders', 'user_id', 'users', 'cascade');
        $this->replaceForeign('orders', 'company_id', 'companies', 'cascade');

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('author_id')->nullable(false)->change();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
        });

        $this->replaceForeign('payments', 'order_id', 'orders', 'cascade');
        $this->replaceForeign('order_items', 'vacancy_id', 'vacancies', 'cascade');
        $this->replaceForeign('order_items', 'package_id', 'packages', 'cascade');
        $this->replaceForeign('order_items', 'order_id', 'orders', 'cascade');
        $this->replaceForeign('import_logs', 'import_id', 'imports', 'cascade');
        $this->replaceForeign('applications', 'vacancy_id', 'vacancies', 'cascade');
        $this->replaceForeign('vacancies', 'company_id', 'companies', 'cascade');
        $this->replaceForeign('companies', 'user_id', 'users', 'cascade');
    }

    private function replaceForeign(
        string $tableName,
        string $column,
        string $parentTable,
        string $onDelete,
    ): void {
        Schema::table($tableName, function (Blueprint $table) use ($column) {
            $table->dropForeign([$column]);
        });

        Schema::table($tableName, function (Blueprint $table) use ($column, $parentTable, $onDelete) {
            $foreign = $table->foreign($column)->references('id')->on($parentTable);

            match ($onDelete) {
                'cascade' => $foreign->cascadeOnDelete(),
                'null' => $foreign->nullOnDelete(),
                default => $foreign->restrictOnDelete(),
            };
        });
    }
};
