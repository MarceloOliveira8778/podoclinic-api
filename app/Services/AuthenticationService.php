<?php

namespace App\Services;

use App\Entities\User;
use App\Models\AuthSessionModel;
use App\Models\UserModel;
use RuntimeException;

class AuthenticationService
{
    private const SESSION_MAX_LIFETIME_HOURS = 12;

    public function login(
        string $email,
        string $password
    ): array {
        $email = $this->normalizeEmail($email);

        $userModel = new UserModel();

        $user = $userModel
            ->where('email', $email)
            ->first();

        if (
            $user === null
            || !$user->is_active
            || !password_verify(
                $password,
                $user->password_hash
            )
        ) {
            throw new RuntimeException(
                'E-mail ou senha inválidos.'
            );
        }

        $this->rehashPasswordIfNecessary(
            $user,
            $password,
            $userModel
        );

        $token = bin2hex(
            random_bytes(32)
        );

        $tokenHash = hash(
            'sha256',
            $token
        );

        $now = new \DateTimeImmutable();

        $expiresAt = $now->modify(
            '+' . self::SESSION_MAX_LIFETIME_HOURS . ' hours'
        );

        $authSessionModel = new AuthSessionModel();

        $authSessionData = [
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'last_activity_at' => $now->format(
                'Y-m-d H:i:s'
            ),
            'expires_at' => $expiresAt->format(
                'Y-m-d H:i:s'
            ),
            'revoked_at' => null,
        ];

        if (!$authSessionModel->insert($authSessionData)) {
            throw new RuntimeException(
                'Não foi possível iniciar a sessão.'
            );
        }

        return [
            'token' => $token,
            'tokenType' => 'Bearer',
            'expiresAt' => $expiresAt->format(
                'Y-m-d H:i:s'
            ),
            'user' => $this->formatUser($user),
        ];
    }

    public function logout(int $authSessionId): void
    {
        $authSessionModel = new AuthSessionModel();

        $authSession = $authSessionModel->find(
            $authSessionId
        );

        if ($authSession === null) {
            return;
        }

        if ($authSession->revoked_at !== null) {
            return;
        }

        $authSessionModel->update(
            $authSessionId,
            [
                'revoked_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(
            trim($email)
        );
    }

    private function rehashPasswordIfNecessary(
        User $user,
        string $password,
        UserModel $userModel
    ): void {
        if (
            !password_needs_rehash(
                $user->password_hash,
                PASSWORD_DEFAULT
            )
        ) {
            return;
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if ($passwordHash === false) {
            return;
        }

        $userModel->update(
            $user->id,
            [
                'password_hash' => $passwordHash,
            ]
        );
    }

    private function formatUser(User $user): array
    {
        return [
            'id'       => $user->id,
            'name'     => $user->name,
            'email'    => $user->email,
            'isActive' => $user->is_active,
        ];
    }
}