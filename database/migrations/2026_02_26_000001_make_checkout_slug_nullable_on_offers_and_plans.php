<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Apenas torna a coluna nullable; o índice unique já existe nas tabelas
        DB::statement('ALTER TABLE product_offers MODIFY checkout_slug VARCHAR(16) NULL');
        DB::statement('ALTER TABLE subscription_plans MODIFY checkout_slug VARCHAR(16) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE product_offers MODIFY checkout_slug VARCHAR(16) NOT NULL');
        DB::statement('ALTER TABLE subscription_plans MODIFY checkout_slug VARCHAR(16) NOT NULL');
    }
};
