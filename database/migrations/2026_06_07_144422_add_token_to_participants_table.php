<?php

use App\Models\Participant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('token', 64)->nullable()->unique()->after('telefono');
        });

        // Genera token para los participantes ya existentes.
        Participant::whereNull('token')->get()->each(function (Participant $participant) {
            $participant->token = Str::random(40);
            $participant->save();
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
};
