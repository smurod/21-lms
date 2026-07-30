<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\GitlabService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_][A-Za-z0-9_.-]*$/',
                'unique:users,username',
                function (string $attribute, mixed $value, callable $fail): void {
                    $reserved = ['admin', 'api', 'assets', 'dashboard', 'explore', 'groups', 'help', 'import', 'profile', 'projects', 'root', 'search', 'users'];

                    if (in_array(strtolower((string) $value), $reserved, true)) {
                        $fail('This username is reserved by GitLab. Choose another username, for example lms-admin or your own login.');
                    }
                },
            ],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => array_merge($this->passwordRules(), [
                function (string $attribute, mixed $value, callable $fail) use ($input): void {
                    $password = strtolower((string) $value);
                    $identityTokens = collect([
                        $input['username'] ?? null,
                        str($input['email'] ?? '')->before('@')->toString(),
                        ...preg_split('/[^A-Za-z0-9]+/', (string) ($input['name'] ?? ''), -1, PREG_SPLIT_NO_EMPTY),
                    ])->filter(fn ($token) => strlen((string) $token) >= 4)
                        ->map(fn ($token) => strtolower((string) $token))
                        ->unique();

                    foreach ($identityTokens as $token) {
                        if (str_contains($password, $token)) {
                            $fail('Password must not contain your username, email login or name because GitLab rejects personal-info passwords. Example: school21.');
                            return;
                        }
                    }
                },
            ]),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ], [
            'password.regex' => 'Password must contain at least one latin letter and one number. Example: school21.',
        ])->validate();

        try {
            $gitlabResult = app(GitlabService::class)->provisionUserAccount(
                email: $input['email'],
                username: $input['username'],
                name: $input['name'],
                password: $input['password']
            );
        } catch (\Throwable $e) {
            Log::error('GitLab user provisioning blocked Fortify registration', [
                'email' => $input['email'],
                'username' => $input['username'],
                'error' => $e->getMessage(),
            ]);

            $error = str_contains(strtolower($e->getMessage()), 'password')
                ? 'Password is too common for GitLab. Use at least 8 characters with latin letters and numbers, for example school21.'
                : 'GitLab API is required for registration, but provisioning failed: ' . $e->getMessage();

            throw ValidationException::withMessages([
                'gitlab' => $error,
            ]);
        }

        $user = User::create([
            'name' => $input['name'],
            'username' => $input['username'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $user->assignRole($userRole);
        $user->connectGitlabToken($gitlabResult['token']);

        return $user;
    }
}
