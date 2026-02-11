<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && (string) Auth::id() === (string) $this->getRouteUserId();
    }

    protected function getRouteUserId(): string|int
    {
        $user = $this->route('user');

        return $user instanceof \App\Models\User ? $user->id : $user;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$this->getRouteUserId(),
            'password' => 'sometimes|nullable|string|min:8|confirmed',
        ];
    }
}
