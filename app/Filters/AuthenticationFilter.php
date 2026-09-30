<?php

namespace App\Filters;

use App\Models\AuthSessionModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\Services;
use DateTimeImmutable;

class AuthenticationFilter implements FilterInterface
{
    private const INACTIVITY_LIMIT_MINUTES = 20;

    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        $token = $this->extractBearerToken($request);

        if ($token === null) {
            return $this->unauthorized(
                'Autenticação necessária.'
            );
        }

        $tokenHash = hash(
            'sha256',
            $token
        );

        $authSessionModel = new AuthSessionModel();

        $authSession = $authSessionModel
            ->where('token_hash', $tokenHash)
            ->first();

        if ($authSession === null) {
            return $this->unauthorized(
                'Sessão inválida ou expirada.'
            );
        }

        if ($authSession->revoked_at !== null) {
            return $this->unauthorized(
                'Sessão inválida ou expirada.'
            );
        }

        $now = new DateTimeImmutable();

        $expiresAt = new DateTimeImmutable(
            $authSession->expires_at
        );

        if ($now >= $expiresAt) {
            return $this->unauthorized(
                'Sessão inválida ou expirada.'
            );
        }

        $lastActivityAt = new DateTimeImmutable(
            $authSession->last_activity_at
        );

        $inactivityLimit = $lastActivityAt->modify(
            '+' . self::INACTIVITY_LIMIT_MINUTES . ' minutes'
        );

        if ($now >= $inactivityLimit) {
            return $this->unauthorized(
                'Sessão expirada por inatividade.'
            );
        }

        $userModel = new UserModel();

        $user = $userModel->find(
            $authSession->user_id
        );

        if (
            $user === null
            || !$user->is_active
        ) {
            return $this->unauthorized(
                'Usuário inativo ou não encontrado.'
            );
        }

        $authSessionModel->update(
            $authSession->id,
            [
                'last_activity_at' => $now->format(
                    'Y-m-d H:i:s'
                ),
            ]
        );

        Services::authenticatedUser()->setUser(
            $user,
            $authSession->id
        );

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        return null;
    }

    private function extractBearerToken(
        RequestInterface $request
    ): ?string {
        $authorizationHeader = trim(
            $request->getHeaderLine('Authorization')
        );

        if ($authorizationHeader === '') {
            return null;
        }

        if (
            preg_match(
                '/^Bearer\s+(\S+)$/i',
                $authorizationHeader,
                $matches
            ) !== 1
        ) {
            return null;
        }

        $token = $matches[1];

        /*
         * Nossos tokens são gerados por:
         *
         * bin2hex(random_bytes(32))
         *
         * portanto possuem exatamente 64 caracteres
         * hexadecimais.
         */
        if (
            preg_match(
                '/^[a-f0-9]{64}$/i',
                $token
            ) !== 1
        ) {
            return null;
        }

        return $token;
    }

    private function unauthorized(
        string $message
    ): ResponseInterface {
        return Services::response()
            ->setStatusCode(401)
            ->setJSON([
                'status' => 401,
                'error' => 401,
                'messages' => [
                    'error' => $message,
                ],
            ]);
    }
}