<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('survey_responses')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('questions');
            $table->foreignId('option_id')->nullable()->constrained('question_options');
            $table->text('text_answer')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
