<?php

namespace App\Services;

use App\Entities\User;
use App\Models\AuthSessionModel;
use App\Models\UserModel;
use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;

class UserService
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function createEmployee(array $data): User
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = $this->normalizeEmail(
            (string) ($data['email'] ?? '')
        );
        $password = (string) ($data['password'] ?? '');

        $this->validateName($name);
        $this->validateEmail($email);
        $this->validatePassword($password);

        $existingUser = $this->userModel
            ->where('email', $email)
            ->first();

        if ($existingUser !== null) {
            throw new BusinessException(
                'Já existe um usuário cadastrado com este e-mail.'
            );
        }

        $employeeRoleId = $this->findEmployeeRoleId();

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if ($passwordHash === false) {
            throw new BusinessException(
                'Não foi possível processar a senha.'
            );
        }

        $userData = [
            'role_id'       => $employeeRoleId,
            'name'          => $name,
            'email'         => $email,
            'password_hash' => $passwordHash,
            'is_active'     => true,
        ];

        $userId = $this->userModel->insert(
            $userData,
            true
        );

        if ($userId === false) {
            throw new BusinessException(
                $this->getModelErrorMessage()
            );
        }

        return $this->userModel->find(
            (int) $userId
        );
    }

    public function updateEmployee(
        int $userId,
        array $data
    ): User {
        $user = $this->findEmployee($userId);

        $updateData = [];
        $passwordChanged = false;

        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);

            $this->validateName($name);

            $updateData['name'] = $name;
        }

        if (array_key_exists('email', $data)) {
            $email = $this->normalizeEmail(
                (string) $data['email']
            );

            $this->validateEmail($email);

            $existingUser = $this->userModel
                ->where('email', $email)
                ->where('id !=', $userId)
                ->first();

            if ($existingUser !== null) {
                throw new BusinessException(
                    'Já existe um usuário cadastrado com este e-mail.'
                );
            }

            $updateData['email'] = $email;
        }

        if (array_key_exists('password', $data)) {
            $password = (string) $data['password'];

            $this->validatePassword($password);

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            if ($passwordHash === false) {
                throw new BusinessException(
                    'Não foi possível processar a senha.'
                );
            }

            $updateData['password_hash'] = $passwordHash;

            $passwordChanged = true;
        }

        if ($updateData === []) {
            throw new BusinessException(
                'Nenhum campo válido foi informado para alteração.'
            );
        }

        $database = db_connect();

        $database->transStart();

        $this->userModel->update(
            $user->id,
            $updateData
        );

        if ($passwordChanged) {
            $this->revokeAllSessions(
                $user->id
            );
        }

        $database->transComplete();

        if (!$database->transStatus()) {
            throw new BusinessException(
                'Não foi possível alterar o usuário.'
            );
        }

        return $this->userModel->find($user->id);
    }

    public function activateEmployee(int $userId): User
    {
        $user = $this->findEmployee($userId);

        if (!$user->is_active) {
            if (
                !$this->userModel->update(
                    $user->id,
                    ['is_active' => true]
                )
            ) {
                throw new BusinessException(
                    $this->getModelErrorMessage()
                );
            }
        }

        return $this->userModel->find($user->id);
    }

    public function deactivateEmployee(int $userId): User
    {
        $user = $this->findEmployee($userId);

        $database = db_connect();

        $database->transStart();

        $this->userModel->update(
            $user->id,
            [
                'is_active' => false,
            ]
        );

        $this->revokeAllSessions(
            $user->id
        );

        $database->transComplete();

        if (!$database->transStatus()) {
            throw new BusinessException(
                'Não foi possível inativar o usuário.'
            );
        }

        return $this->userModel->find($user->id);
    }

    public function findEmployee(int $userId): User
    {
        $user = $this->userModel->find($userId);

        if ($user === null) {
            throw new ResourceNotFoundException(
                'Usuário não encontrado.'
            );
        }

        $database = db_connect();

        $employeeRole = $database
            ->table('role')
            ->select('id')
            ->where('name', 'EMPLOYEE')
            ->get()
            ->getRowArray();

        if (
            $employeeRole === null
            || $user->role_id !== (int) $employeeRole['id']
        ) {
            throw new ResourceNotFoundException(
                'Usuário não encontrado.'
            );
        }

        return $user;
    }

    private function findEmployeeRoleId(): int
    {
        $database = db_connect();

        $role = $database
            ->table('role')
            ->select('id')
            ->where('name', 'EMPLOYEE')
            ->where('is_active', true)
            ->get()
            ->getRowArray();

        if ($role === null) {
            throw new BusinessException(
                'Perfil EMPLOYEE não encontrado ou inativo.'
            );
        }

        return (int) $role['id'];
    }

    private function validateName(string $name): void
    {
        if ($name === '') {
            throw new BusinessException(
                'O nome é obrigatório.'
            );
        }

        if (mb_strlen($name) > 120) {
            throw new BusinessException(
                'O nome deve possuir no máximo 120 caracteres.'
            );
        }
    }

    private function validateEmail(string $email): void
    {
        if (
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            throw new BusinessException(
                'Informe um e-mail válido.'
            );
        }

        if (mb_strlen($email) > 190) {
            throw new BusinessException(
                'O e-mail deve possuir no máximo 190 caracteres.'
            );
        }
    }

    private function validatePassword(string $password): void
    {
        $passwordLength = strlen($password);

        if ($passwordLength < 10) {
            throw new BusinessException(
                'A senha deve possuir pelo menos 10 caracteres.'
            );
        }

        if ($passwordLength > 72) {
            throw new BusinessException(
                'A senha deve possuir no máximo 72 caracteres.'
            );
        }
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(
            trim($email)
        );
    }

    private function getModelErrorMessage(): string
    {
        $errors = $this->userModel->errors();

        if ($errors === []) {
            return 'Não foi possível salvar o usuário.';
        }

        return implode(
            PHP_EOL,
            array_values($errors)
        );
    }

    private function revokeAllSessions(
        int $userId
    ): void {
        $authSessionModel = new AuthSessionModel();

        $authSessionModel
            ->where('user_id', $userId)
            ->where('revoked_at', null)
            ->set([
                'revoked_at' => date('Y-m-d H:i:s'),
            ])
            ->update();
    }
}