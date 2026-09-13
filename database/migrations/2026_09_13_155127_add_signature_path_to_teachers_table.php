<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Firma escaneada del docente, para el certificado (docs/PLAN_ARQUITECTONICO.md
 * §6, "Modelo visual definitivo, con firma escaneada"). Va al disco `public`,
 * como `content_file` de `class_content` — mientras no haya Google Cloud
 * Storage, los archivos van al disco público local.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('specialization');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });
    }
};
