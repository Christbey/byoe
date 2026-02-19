<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'role' => ['required', 'in:shop_owner,provider'],
            'terms' => ['required', 'accepted'],
        ], [
            'role.required' => 'Please select whether you are a Shop Owner or a Contractor.',
            'role.in' => 'Invalid account type selected.',
            'terms.required' => 'You must accept the Terms of Service to create an account.',
            'terms.accepted' => 'You must accept the Terms of Service to create an account.',
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'terms_accepted_at' => now(),
        ]);

        $user->assignRole($input['role']);

        return $user;
    }
}
