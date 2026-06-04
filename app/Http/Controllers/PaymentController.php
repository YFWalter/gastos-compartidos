<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use App\Models\InstallmentShare;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    /**
     * Alterna el estado de pago de una participación (parte de un participante en una cuota).
     */
    public function toggleShare(InstallmentShare $share): RedirectResponse
    {
        $this->authorizeShare($share);

        $share->estaPagada() ? $share->marcarPendiente() : $share->marcarPagada();

        return back()->with('status', 'Pago actualizado.');
    }

    /**
     * Alterna el estado de pago de una cuota completa (todas sus participaciones).
     */
    public function toggleInstallment(Installment $installment): RedirectResponse
    {
        $this->authorizeInstallment($installment);

        $installment->estaPagada() ? $installment->marcarPendiente() : $installment->marcarPagada();

        return back()->with('status', 'Cuota actualizada.');
    }

    private function authorizeShare(InstallmentShare $share): void
    {
        abort_unless(
            $share->installment->purchase->user_id === auth()->id(),
            403
        );
    }

    private function authorizeInstallment(Installment $installment): void
    {
        abort_unless(
            $installment->purchase->user_id === auth()->id(),
            403
        );
    }
}
