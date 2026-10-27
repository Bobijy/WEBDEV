<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(
    fields: ['email'],
    entityClass: User::class,
    identifierFieldNames: ['id'],
    message: 'An account with this email already exists.',
)]
final class ProfileData
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $fullName = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public string $email = '';

    #[Assert\Length(max: 20)]
    public ?string $phone = null;

    public ?string $address = null;
    public ?string $gender = null;
    public ?\DateTimeInterface $dob = null;

    public function __construct(public readonly ?int $id = null)
    {
    }

    public static function fromUser(User $user): self
    {
        $profile = new self($user->getId());
        $profile->fullName = $user->getFullName();
        $profile->email = $user->getEmail();
        $profile->phone = $user->getPhone();
        $profile->address = $user->getAddress();
        $profile->gender = $user->getGender();
        $profile->dob = $user->getDob();

        return $profile;
    }

    public function setEmail(string $email): void
    {
        $this->email = mb_strtolower(trim($email));
    }

    public function applyTo(User $user): void
    {
        $user->setFullName($this->fullName)
            ->setEmail($this->email)
            ->setPhone($this->phone)
            ->setAddress($this->address)
            ->setGender($this->gender)
            ->setDob($this->dob);
    }
}
