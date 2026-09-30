<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Models\UserModel;
use App\Services\UserService;
use CodeIgniter\API\ResponseTrait;
use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;

class UserController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $userModel = new UserModel();

        $users = $userModel
            ->select('user.*')
            ->join(
                'role',
                'role.id = user.role_id'
            )
            ->where('role.name', 'EMPLOYEE')
            ->orderBy('user.name', 'ASC')
            ->findAll();

        return $this->respond(
            array_map(
                fn (User $user) =>
                    $this->formatUser($user),
                $users
            )
        );
    }

    public function show(int $id)
    {
        try {
            $userService = new UserService();

            return $this->respond(
                $this->formatUser(
                    $userService->findEmployee($id)
                )
            );
        } catch (ResourceNotFoundException $exception) {
            return $this->failNotFound(
                $exception->getMessage()
            );
        }
    }

    public function create()
    {
        $requestData = $this->request->getJSON(true);

        if (!is_array($requestData)) {
            return $this->failValidationErrors(
                'Dados da requisição inválidos.'
            );
        }

        try {
            $userService = new UserService();

            $user = $userService->createEmployee(
                $requestData
            );

            return $this->respondCreated(
                $this->formatUser($user)
            );
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        }
    }

    public function update(int $id)
    {
        $requestData = $this->request->getJSON(true);

        if (!is_array($requestData)) {
            return $this->failValidationErrors(
                'Dados da requisição inválidos.'
            );
        }

        try {
            $userService = new UserService();

            $user = $userService->updateEmployee(
                $id,
                $requestData
            );

            return $this->respond(
                $this->formatUser($user)
            );
        } catch (ResourceNotFoundException $exception) {
            return $this->failNotFound(
                $exception->getMessage()
            );
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        }
    }

    public function activate(int $id)
    {
        try {
            $userService = new UserService();

            $user = $userService->activateEmployee($id);

            return $this->respond(
                $this->formatUser($user)
            );
        } catch (ResourceNotFoundException $exception) {
            return $this->failNotFound(
                $exception->getMessage()
            );
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        }
    }

    public function deactivate(int $id)
    {
        try {
            $userService = new UserService();

            $user = $userService->deactivateEmployee($id);

            return $this->respond(
                $this->formatUser($user)
            );
        } catch (ResourceNotFoundException $exception) {
            return $this->failNotFound(
                $exception->getMessage()
            );
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        }
    }

    private function formatUser(User $user): array
    {
        return [
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'isActive'  => $user->is_active,
            'createdAt' => $user->created_at,
            'updatedAt' => $user->updated_at,
        ];
    }
}