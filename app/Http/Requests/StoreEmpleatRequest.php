<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Empleat;

class StoreEmpleatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->rol_principal === 'rrhh' || $this->user()->rol_principal === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom_complet' => 'required|string|max:255',
            'nif' => 'required|string|max:20|unique:empleats,nif,NULL,id,estat,actiu',
            'correu_personal' => 'required|email|max:255',
            'departament_id' => 'required|exists:departaments,id',
            'carrec' => 'required|string|max:255',
            'observacions' => 'nullable|string'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'nom_complet.required' => 'El nom complet és obligatori',
            'nif.required' => 'El NIF és obligatori',
            'nif.unique' => 'Ja existeix un empleat actiu amb aquest NIF',
            'correu_personal.required' => 'El correu personal és obligatori',
            'correu_personal.email' => 'El correu personal ha de ser una adreça vàlida',
            'departament_id.required' => 'El departament és obligatori',
            'departament_id.exists' => 'El departament seleccionat no existeix',
            'carrec.required' => 'El càrrec és obligatori',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->nifJaExisteixActiu()) {
                $validator->errors()->add('nif', 'Ja existeix un empleat actiu amb aquest NIF');
            }
        });
    }

    /**
     * Comprovar si ja existeix un empleat actiu amb aquest NIF
     */
    protected function nifJaExisteixActiu(): bool
    {
        return Empleat::where('nif', $this->nif)
            ->where('estat', 'actiu')
            ->exists();
    }
}
