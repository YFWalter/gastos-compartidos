<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->decimal('porcentaje', 5, 2);
            $table->timestamps();
            $table->unique(['service_id', 'participant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_splits');
    }
};
