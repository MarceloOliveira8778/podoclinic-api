<?php

namespace App\Services;

use App\Entities\User;

class AuthenticatedUserService
{
    private ?User $user = null;

    private ?int $authSessionId = null;

    public function setUser(
        User $user,
        int $authSessionId
    ): void {
        $this->user = $user;
        $this->authSessionId = $authSessionId;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getUserId(): ?int
    {
        return $this->user?->id;
    }

    public function getAuthSessionId(): ?int
    {
        return $this->authSessionId;
    }

    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }
}