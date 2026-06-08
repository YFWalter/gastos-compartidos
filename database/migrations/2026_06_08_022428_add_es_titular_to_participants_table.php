<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->boolean('es_titular')->default(false)->after('token');
        });

        // Crea el participante "titular" para los usuarios que ya existen.
        User::whereDoesntHave('participants', fn ($q) => $q->where('es_titular', true))
            ->get()
            ->each(fn (User $user) => $user->crearParticipanteTitular());
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('es_titular');
        });
    }
};
