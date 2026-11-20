<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Amichiamoci\Models\Token;

foreach (['fixture1', bin2hex(random_bytes(32))] as $secret) {
    $token = new Token(
        value: hash('sha256', $secret),
        secret: '',
        generation_date: 'now',
        expiration_date: '+2 hours',
        usage_date: null,
        user_id: 1,
        email: 'fixture@example.invalid',
    );

    if (!$token->Matches($secret) || $token->Matches('incorrect') || $token->IsUsed() || $token->IsExpired()) {
        throw new RuntimeException('Password reset token validation failed.');
    }
}

echo "Password reset token checks passed.\n";
