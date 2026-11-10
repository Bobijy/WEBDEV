<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\UserAccountData;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserManagementService
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher)
    {
    }

    public function save(User $actor, User $user, UserAccountData $data): void
    {
        $this->em->getConnection()->transactional(function () use ($actor, $user, $data): void {
            $this->lockAdministration($actor, $user);
            if ($user->getId() === $actor->getId() && ($data->role !== 'admin' || !$data->active)) {
                throw new \DomainException('You cannot change your own role or disable your own account. Ask another administrator.');
            }
            $user->setFullName($data->fullName)->setEmail($data->email)->setRole($data->role)->setActive($data->active);
            if ($data->plainPassword !== null && $data->plainPassword !== '') {
                $user->setPassword($this->hasher->hashPassword($user, $data->plainPassword));
            }
            $this->em->persist($user);
            $this->em->flush();
        });
    }

    public function delete(User $actor, User $user): void
    {
        $this->em->getConnection()->transactional(function () use ($actor, $user): void {
            $this->lockAdministration($actor, $user);
            if ($actor->getId() === $user->getId()) {
                throw new \DomainException('You cannot delete yourself.');
            }
            if ($this->em->getRepository(Order::class)->count(['user' => $user]) > 0) {
                throw new \DomainException('Users with order history cannot be deleted. Disable the account instead.');
            }
            $this->em->remove($user);
            $this->em->flush();
        });
    }

    private function lockAdministration(User $actor, User $target): void
    {
        // Serialize account administration so concurrent admins cannot remove each other's access.
        $this->em->createQueryBuilder()->select('u')->from(User::class, 'u')
            ->where('u.role = :role')->setParameter('role', 'admin')->orderBy('u.id', 'ASC')
            ->getQuery()->setLockMode(LockMode::PESSIMISTIC_WRITE)->getResult();
        $this->em->refresh($actor);
        if ($actor->getRole() !== 'admin' || !$actor->isActive()) {
            throw new AccessDeniedHttpException('Administrator access is required.');
        }
        if ($target->getId() !== null) {
            $this->em->refresh($target, LockMode::PESSIMISTIC_WRITE);
        }
    }
}
