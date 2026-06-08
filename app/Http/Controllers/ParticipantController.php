<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParticipantRequest;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParticipantController extends Controller
{
    /**
     * Listado de participantes del usuario autenticado.
     */
    public function index(Request $request): View
    {
        $participants = $request->user()->participants()
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->paginate(15);

        return view('participants.index', compact('participants'));
    }

    /**
     * Formulario de alta.
     */
    public function create(): View
    {
        return view('participants.create');
    }

    /**
     * Guardar un nuevo participante.
     */
    public function store(ParticipantRequest $request): RedirectResponse
    {
        $request->user()->participants()->create($request->validated());

        return redirect()->route('participants.index')
            ->with('status', 'Participante creado correctamente.');
    }

    /**
     * Formulario de edición.
     */
    public function edit(Participant $participant): View
    {
        $this->authorizeParticipant($participant);

        return view('participants.edit', compact('participant'));
    }

    /**
     * Actualizar un participante existente.
     */
    public function update(ParticipantRequest $request, Participant $participant): RedirectResponse
    {
        $this->authorizeParticipant($participant);

        $participant->update($request->validated());

        return redirect()->route('participants.index')
            ->with('status', 'Participante actualizado correctamente.');
    }

    /**
     * Eliminar un participante.
     */
    public function destroy(Participant $participant): RedirectResponse
    {
        $this->authorizeParticipant($participant);

        if ($participant->es_titular) {
            return redirect()->route('participants.index')
                ->with('status', 'No se puede eliminar tu propio participante (titular).');
        }

        $participant->delete();

        return redirect()->route('participants.index')
            ->with('status', 'Participante eliminado.');
    }

    /**
     * Regenera el token del participante (invalida el link de invitado anterior).
     */
    public function regenerarToken(Participant $participant): RedirectResponse
    {
        $this->authorizeParticipant($participant);

        $participant->regenerarToken();

        return redirect()->route('participants.edit', $participant)
            ->with('status', 'Se generó un nuevo link de invitado. El anterior dejó de funcionar.');
    }

    /**
     * Asegura que el participante pertenece al usuario autenticado.
     */
    private function authorizeParticipant(Participant $participant): void
    {
        abort_unless($participant->user_id === auth()->id(), 403);
    }
}
