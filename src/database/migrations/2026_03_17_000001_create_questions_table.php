<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('surveys')->onDelete('cascade');
            $table->string('type');
            $table->text('content');
            $table->integer('order_index')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
