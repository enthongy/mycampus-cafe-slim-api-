<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

const JWT_SECRET = 'mycampus_cafe_secret_key_change_this';
const JWT_ALG = 'HS256';
const JWT_EXPIRY_SECONDS = 3600; // 1 hour

function bcryptHash(string $plainPassword): string
{
    return password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
}

function bcryptVerify(string $plainPassword, string $hash): bool
{
    return password_verify($plainPassword, $hash);
}

function createJwtToken(array $staff): string
{
    $issuedAt = time();
    $expire = $issuedAt + JWT_EXPIRY_SECONDS;

    $payload = [
        'staff_id' => $staff['staff_id'],
        'username' => $staff['username'],
        'role' => $staff['role'],
        'iat' => $issuedAt,
        'exp' => $expire
    ];

    return JWT::encode($payload, JWT_SECRET, JWT_ALG);
}
