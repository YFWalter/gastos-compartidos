<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'descripcion'         => ['required', 'string', 'max:255'],
            'monto_total'         => ['required', 'numeric', 'min:0.01', 'max:99999999999'],
            'cantidad_cuotas'     => ['required', 'integer', 'min:1', 'max:360'],
            'fecha_primera_cuota' => ['required', 'date'],
            'notas'               => ['nullable', 'string', 'max:2000'],

            'splits'                  => ['required', 'array', 'min:1'],
            'splits.*.participant_id' => [
                'required',
                Rule::exists('participants', 'id')->where('user_id', $this->user()->id),
            ],
            'splits.*.porcentaje'     => ['required', 'numeric', 'min:0.01', 'max:100'],
        ];
    }

    /**
     * Validaciones a nivel del conjunto de splits.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $splits = $this->input('splits', []);

            // Los porcentajes deben sumar 100%.
            $suma = array_sum(array_map(
                fn ($s) => (float) ($s['porcentaje'] ?? 0),
                $splits
            ));
            if (abs($suma - 100) > 0.01) {
                $validator->errors()->add(
                    'splits',
                    'Los porcentajes deben sumar 100%. Suma actual: ' . rtrim(rtrim(number_format($suma, 2, '.', ''), '0'), '.') . '%.'
                );
            }

            // No se puede repetir el mismo participante.
            $ids = array_filter(array_map(fn ($s) => $s['participant_id'] ?? null, $splits));
            if (count($ids) !== count(array_unique($ids))) {
                $validator->errors()->add('splits', 'No repitas el mismo participante en el reparto.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'descripcion'         => 'descripción',
            'monto_total'         => 'monto total',
            'cantidad_cuotas'     => 'cantidad de cuotas',
            'fecha_primera_cuota' => 'fecha de la primera cuota',
        ];
    }
}
