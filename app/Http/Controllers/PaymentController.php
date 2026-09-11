<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use App\Models\InstallmentShare;
use App\Models\ServiceCharge;
use App\Models\ServiceChargeShare;
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

    /**
     * Alterna el estado de pago de una participación en un cargo de servicio.
     */
    public function toggleServiceChargeShare(ServiceChargeShare $share): RedirectResponse
    {
        $this->authorizeServiceChargeShare($share);

        $share->estaPagada() ? $share->marcarPendiente() : $share->marcarPagada();

        return back()->with('status', 'Pago actualizado.');
    }

    /**
     * Alterna el estado de pago de un cargo de servicio completo (todas sus participaciones).
     */
    public function toggleServiceCharge(ServiceCharge $charge): RedirectResponse
    {
        $this->authorizeServiceCharge($charge);

        $charge->estaPagado() ? $charge->marcarPendiente() : $charge->marcarPagado();

        return back()->with('status', 'Cargo actualizado.');
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

    private function authorizeServiceChargeShare(ServiceChargeShare $share): void
    {
        abort_unless(
            $share->serviceCharge->service->user_id === auth()->id(),
            403
        );
    }

    private function authorizeServiceCharge(ServiceCharge $charge): void
    {
        abort_unless(
            $charge->service->user_id === auth()->id(),
            403
        );
    }
}
