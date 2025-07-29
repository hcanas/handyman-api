<?php

namespace App\Rules;

use App\Models\PasswordOtp;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidOtp implements ValidationRule
{
    public function __construct(
        private readonly ?string $email,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password_otp = PasswordOtp::query()
            ->where('email', $this->email)
            ->where('otp', $value)
            ->where('expired_at', '>', now())
            ->first();

        if (! $password_otp) {
            $fail('The otp is invalid');
        }
    }
}
