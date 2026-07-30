<?php

namespace App\Actions\Fortify;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * The rule mirrors the local GitLab policy found by API/rails checks:
     * 8+ chars, at least one latin letter and one digit, and no known common
     * combinations that GitLab rejects before creating an account.
     *
     * @return array<int, Rule|array<mixed>|string|callable>
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            Password::min(8),
            'regex:/[A-Za-z]/',
            'regex:/[0-9]/',
            function (string $attribute, mixed $value, callable $fail): void {
                $blocked = [
                    '00000000',
                    '11111111',
                    '22222222',
                    '12121212',
                    '12345678',
                    '87654321',
                    '12341234',
                    'abcd1234',
                    'a1234567',
                    'gitlab21',
                    'a1b2c3d4',
                ];

                if (in_array(strtolower((string) $value), $blocked, true)) {
                    $fail('Password is too common for GitLab. Use at least 8 characters with latin letters and numbers, for example school21.');
                }
            },
            'confirmed',
        ];
    }
}
