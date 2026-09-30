<?php

namespace App\Filters;

use App\Services\PermissionService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class PermissionFilter implements FilterInterface
{
    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        $authenticatedUser =
            Services::authenticatedUser();

        $userId = $authenticatedUser->getUserId();

        if ($userId === null) {
            return $this->unauthorized();
        }

        $permissionName = $arguments[0] ?? null;

        if (
            $permissionName === null
            || trim($permissionName) === ''
        ) {
            return $this->forbidden(
                'Permissão não configurada para esta rota.'
            );
        }

        $permissionService = new PermissionService();

        if (
            !$permissionService->userHasPermission(
                $userId,
                $permissionName
            )
        ) {
            return $this->forbidden(
                'Você não possui permissão para realizar esta operação.'
            );
        }

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        return null;
    }

    private function unauthorized(): ResponseInterface
    {
        return Services::response()
            ->setStatusCode(401)
            ->setJSON([
                'status' => 401,
                'error' => 401,
                'messages' => [
                    'error' => 'Autenticação necessária.',
                ],
            ]);
    }

    private function forbidden(
        string $message
    ): ResponseInterface {
        return Services::response()
            ->setStatusCode(403)
            ->setJSON([
                'status' => 403,
                'error' => 403,
                'messages' => [
                    'error' => $message,
                ],
            ]);
    }
}