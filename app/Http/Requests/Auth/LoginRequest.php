<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', Rule::when(
                ! filter_var($this->input('identifier'), FILTER_VALIDATE_EMAIL),
                ['digits:6'],
            )],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identifier = $this->string('identifier')->trim()->toString();
        $authenticated = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? Auth::attempt([
                'email' => Str::lower($identifier),
                'password' => $this->input('password'),
                'activo' => true,
            ], $this->boolean('remember'))
            : $this->authenticateStudent($identifier);

        if (! $authenticated) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'identifier' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'identifier' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('identifier')).'|'.$this->ip());
    }

    private function authenticateStudent(string $dni): bool
    {
        if (! preg_match('/^\d{8}$/', $dni)) {
            return false;
        }

        $user = User::query()
            ->where('activo', true)
            ->whereHas('roleDefinition', fn ($query) => $query->where('nombre', 'estudiante'))
            ->whereHas('estudiante', fn ($query) => $query->where('dni', $dni))
            ->first();

        if (! $user || ! Hash::check($this->input('password'), $user->password)) {
            return false;
        }

        Auth::login($user, $this->boolean('remember'));

        return true;
    }
}
