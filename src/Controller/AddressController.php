<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\UserAddress;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AddressController extends AbstractController
{
    #[Route('/api/address.php', name: 'api_address', methods: ['POST'])]
    public function handleLegacyAddress(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if (!$this->isCsrfTokenValid('address', $request->request->get('_token'))) {
            return $this->json(['success' => false, 'message' => 'Please reload the page and try again.'], 403);
        }
        $action = $request->request->get('action', '');
        if (!in_array($action, ['add', 'edit', 'delete', 'set_default'], true)) {
            return $this->json(['success' => false, 'message' => 'Invalid action.'], 400);
        }
        $user = $this->getUser();
        $values = [];
        if (in_array($action, ['add', 'edit'], true)) {
            foreach (['label' => 50, 'full_name' => 100, 'phone' => 20, 'address_line' => 1500] as $field => $max) {
                $values[$field] = trim($request->request->get($field, $field === 'label' ? 'Home' : ''));
                if ($values[$field] === '' || mb_strlen($values[$field]) > $max) {
                    return $this->json(['success' => false, 'message' => 'Please complete all required fields using valid values.'], 422);
                }
            }
        }

        return $em->wrapInTransaction(function () use ($em, $user, $request, $action, $values): JsonResponse {
            $em->refresh($user, LockMode::PESSIMISTIC_WRITE);
            $repository = $em->getRepository(UserAddress::class);
            $addresses = $repository->findBy(['user' => $user], ['id' => 'ASC']);
            $address = $action === 'add' ? (new UserAddress())->setUser($user) : $repository->findOneBy(['id' => $request->request->getInt('id'), 'user' => $user]);
            if (!$address) {
                return $this->json(['success' => false, 'message' => 'Address not found.'], 404);
            }
            if ($action === 'delete') {
                $em->remove($address);
                $remaining = array_values(array_filter($addresses, fn ($item) => $item !== $address));
                if ($address->isDefault()) {
                    if ($remaining) {
                        $remaining[0]->setIsDefault(true);
                        $user->setAddress($remaining[0]->getAddressLine())->setPhone($remaining[0]->getPhone());
                    } else {
                        $user->setAddress(null)->setPhone(null);
                    }
                }
                return $this->json(['success' => true, 'message' => 'Address deleted.']);
            }
            if ($values) {
                $address->setLabel($values['label'])->setFullName($values['full_name'])->setPhone($values['phone'])->setAddressLine($values['address_line']);
            }
            $isDefault = $action === 'set_default' || $request->request->has('is_default') || !$addresses || $address->isDefault();
            if ($isDefault) {
                foreach ($addresses as $item) {
                    $item->setIsDefault(false);
                }
                $user->setAddress($address->getAddressLine())->setPhone($address->getPhone());
            }
            $address->setIsDefault($isDefault);
            $em->persist($address);

            return $this->json(['success' => true, 'message' => 'Address saved successfully.']);
        });
    }
}
