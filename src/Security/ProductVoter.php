<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ProductVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['PRODUCT_EDIT', 'PRODUCT_DELETE'], true) && $subject instanceof Product;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        return $user instanceof User && $user->isActive() && (
            $user->getRole() === 'admin'
            || ($user->getRole() === 'staff' && $subject->getCreatedBy()?->getId() === $user->getId())
        );
    }
}
