<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role', function (Blueprint $table) {
            $table->id();
            $table->string('name_role');
        });

        // Заполняем данными для тестов и работы
        DB::table('role')->insert([
            ['id' => 1, 'name_role' => 'Author'],
            ['id' => 2, 'name_role' => 'Listener'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('role');
    }
};
