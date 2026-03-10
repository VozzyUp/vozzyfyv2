<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration do plugin Example.
 * Use prefixo "plugin_{slug}_" no nome do arquivo para evitar conflito com outras migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_example_demo', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_example_demo');
    }
};
