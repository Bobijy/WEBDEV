<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['email'], entityClass: User::class, identifierFieldNames: ['id'], message: 'An account with this email already exists.')]
final class UserAccountData
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $fullName = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public string $email = '';

    #[Assert\Choice(['customer', 'staff', 'admin'])]
    public string $role = 'staff';

    public bool $active = true;

    #[Assert\Length(min: 12, max: 72, countUnit: 'bytes')]
    public ?string $plainPassword = null;

    public function __construct(public readonly ?int $id = null)
    {
    }

    public static function fromUser(User $user): self
    {
        $data = new self($user->getId());
        $data->fullName = $user->getFullName();
        $data->email = $user->getEmail();
        $data->role = $user->getRole();
        $data->active = $user->isActive();
        return $data;
    }

    public function setEmail(string $email): void
    {
        $this->email = mb_strtolower(trim($email));
    }
}
