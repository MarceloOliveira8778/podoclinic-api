<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\AuthenticationService;
use CodeIgniter\API\ResponseTrait;
use RuntimeException;

class AuthenticationController extends BaseController
{
    use ResponseTrait;

    public function login()
    {
        $loginRequest = $this->request->getJSON(true);

        if (!is_array($loginRequest)) {
            return $this->failValidationErrors(
                'Dados da requisição inválidos.'
            );
        }

        $email = trim(
            (string) ($loginRequest['email'] ?? '')
        );

        $password = (string) (
            $loginRequest['password'] ?? ''
        );

        if ($email === '' || $password === '') {
            return $this->failValidationErrors([
                'credentials' =>
                    'E-mail e senha são obrigatórios.',
            ]);
        }

        if (
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            return $this->failValidationErrors([
                'email' =>
                    'Informe um e-mail válido.',
            ]);
        }

        try {
            $authenticationService =
                new AuthenticationService();

            $authentication = $authenticationService
                ->login(
                    $email,
                    $password
                );

            return $this->respond(
                $authentication
            );
        } catch (RuntimeException $exception) {
            return $this->failUnauthorized(
                $exception->getMessage()
            );
        }
    }

    public function logout()
    {
        $authenticatedUser =
            \Config\Services::authenticatedUser();

        $authSessionId =
            $authenticatedUser->getAuthSessionId();

        if ($authSessionId === null) {
            return $this->failUnauthorized(
                'Sessão não autenticada.'
            );
        }

        $authenticationService =
            new AuthenticationService();

        $authenticationService->logout(
            $authSessionId
        );

        return $this->respond([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }
}