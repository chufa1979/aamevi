<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Reemplaza `cell_phone`, `delegation` y `sub_delegation` —sin uso en ninguna
 * pantalla ni regla de negocio— por los campos que pide el registro real: país
 * y ciudad de residencia, egreso, universidad, profesión y N° de socio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['cell_phone', 'delegation', 'sub_delegation']);

            $table->string('country', 100)->nullable()->after('phone');
            $table->string('city', 100)->nullable()->after('country');
            $table->date('graduation_date')->nullable()->after('date_of_birth');
            $table->string('university', 255)->nullable()->after('graduation_date');
            $table->string('profession', 255)->nullable()->after('university');
            $table->string('membership_number', 50)->nullable()->after('profession');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['country', 'city', 'graduation_date', 'university', 'profession', 'membership_number']);

            $table->string('cell_phone', 20)->nullable();
            $table->string('sub_delegation', 100)->nullable();
            $table->string('delegation', 100)->nullable();
        });
    }
};
