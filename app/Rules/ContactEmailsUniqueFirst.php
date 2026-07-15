<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use App\Support\ContactEmailList;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ContactEmailsUniqueFirst implements ValidationRule
{
    public function __construct(
        private readonly int|string|null $ignoreUserId = null,
    ) {}

    /**
     * @param  Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $first = ContactEmailList::firstValidEmail((string) $value);
        if ($first === null) {
            $fail(__('Please enter at least one valid email address.'));

            return;
        }

        $query = User::query()->where('email', $first);
        if ($this->ignoreUserId !== null) {
            $query->where('id', '!=', $this->ignoreUserId);
        }

        if ($query->exists()) {
            $fail(__('The first email address is already in use.'));
        }
    }
}
