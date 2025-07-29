<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCompanyEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Only allow emails with valid company domains
        $allowedDomains = ['com', 'net', 'org', 'ph'];

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail("The :attribute must be a valid email address.");
            return;
        }

        $domainParts = explode('.', explode('@', $value)[1] ?? '');
        $tld = end($domainParts);

        if (!in_array($tld, $allowedDomains)) {
            $fail("The :attribute must use a valid company domain (e.g., .com, .net, .org, .ph).");
        }
    }
}
