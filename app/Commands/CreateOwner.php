<?php

namespace App\Commands;

use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RuntimeException;

class CreateOwner extends BaseCommand
{
    protected $group = 'Users';

    protected $name = 'user:create-owner';

    protected $description =
        'Cria o usuário proprietário inicial do sistema.';

    protected $usage = 'user:create-owner';

    public function run(array $params)
    {
        try {
            $this->ensureOwnerDoesNotExist();

            $name = trim(
                CLI::prompt('Nome')
            );

            $email = strtolower(
                trim(CLI::prompt('E-mail'))
            );

            $password = CLI::prompt(
                'Senha',
                null,
                'required'
            );

            $passwordConfirmation = CLI::prompt(
                'Confirme a senha',
                null,
                'required'
            );

            $this->validateInput(
                $name,
                $email,
                $password,
                $passwordConfirmation
            );

            $ownerRoleId = $this->findOwnerRoleId();

            $userModel = new UserModel();

            $existingUser = $userModel
                ->where('email', $email)
                ->first();

            if ($existingUser !== null) {
                throw new RuntimeException(
                    'Já existe um usuário cadastrado com este e-mail.'
                );
            }

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            if ($passwordHash === false) {
                throw new RuntimeException(
                    'Não foi possível gerar o hash da senha.'
                );
            }

            $userData = [
                'role_id'       => $ownerRoleId,
                'name'          => $name,
                'email'         => $email,
                'password_hash' => $passwordHash,
                'is_active'     => true,
            ];

            if (!$userModel->insert($userData)) {
                $errors = $userModel->errors();

                throw new RuntimeException(
                    implode(
                        PHP_EOL,
                        array_values($errors)
                    )
                );
            }

            CLI::newLine();

            CLI::write(
                'Usuário OWNER criado com sucesso.',
                'green'
            );
        } catch (RuntimeException $exception) {
            CLI::newLine();

            CLI::error(
                $exception->getMessage()
            );
        }
    }

    private function validateInput(
        string $name,
        string $email,
        string $password,
        string $passwordConfirmation
    ): void {
        if ($name === '') {
            throw new RuntimeException(
                'O nome é obrigatório.'
            );
        }

        if (mb_strlen($name) > 120) {
            throw new RuntimeException(
                'O nome deve possuir no máximo 120 caracteres.'
            );
        }

        if (
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            throw new RuntimeException(
                'Informe um e-mail válido.'
            );
        }

        if (mb_strlen($email) > 190) {
            throw new RuntimeException(
                'O e-mail deve possuir no máximo 190 caracteres.'
            );
        }

        $passwordLength = strlen($password);

        if ($passwordLength < 10) {
            throw new RuntimeException(
                'A senha deve possuir pelo menos 10 caracteres.'
            );
        }

        if ($passwordLength > 72) {
            throw new RuntimeException(
                'A senha deve possuir no máximo 72 caracteres.'
            );
        }

        if ($password !== $passwordConfirmation) {
            throw new RuntimeException(
                'A confirmação da senha não corresponde à senha informada.'
            );
        }
    }

    private function findOwnerRoleId(): int
    {
        $database = db_connect();

        $ownerRole = $database
            ->table('role')
            ->select('id')
            ->where('name', 'OWNER')
            ->where('is_active', true)
            ->get()
            ->getRowArray();

        if ($ownerRole === null) {
            throw new RuntimeException(
                'Perfil OWNER não encontrado ou inativo. Execute o AuthenticationSeeder.'
            );
        }

        return (int) $ownerRole['id'];
    }

    private function ensureOwnerDoesNotExist(): void
    {
        $database = db_connect();

        $owner = $database
            ->table('user')
            ->select('user.id')
            ->join('role', 'role.id = user.role_id')
            ->where('role.name', 'OWNER')
            ->get()
            ->getRowArray();

        if ($owner !== null) {
            throw new RuntimeException(
                'Já existe um proprietário cadastrado no sistema.'
            );
        }
    }
}