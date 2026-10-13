<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
#[ORM\UniqueConstraint(name: 'UNIQ_CHECKOUT_TOKEN', columns: ['checkout_token'])]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $order_number = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $checkoutToken = null;

    public function getCheckoutToken(): ?string
    {
        return $this->checkoutToken;
    }

    public function setCheckoutToken(string $checkoutToken): static
    {
        $this->checkoutToken = $checkoutToken;

        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $total_amount = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $shipping_address = null;

    #[ORM\Column(length: 50)]
    private ?string $payment_method = null;

    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'Pending'])]
    private ?string $status = 'Pending';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $discount_amount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $shipping_fee = '0.00';

    #[ORM\Column(length: 4, nullable: true)]
    private ?string $card_last4 = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $card_brand = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $card_name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $transaction_id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'])]
    private Collection $orderItems;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->orderItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->order_number;
    }

    public function setOrderNumber(?string $order_number): static
    {
        $this->order_number = $order_number;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->total_amount;
    }

    public function setTotalAmount(string $total_amount): static
    {
        $this->total_amount = $total_amount;
        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shipping_address;
    }

    public function setShippingAddress(string $shipping_address): static
    {
        $this->shipping_address = $shipping_address;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->payment_method;
    }

    public function setPaymentMethod(string $payment_method): static
    {
        $this->payment_method = $payment_method;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getDiscountAmount(): ?string
    {
        return $this->discount_amount;
    }

    public function setDiscountAmount(?string $discount_amount): static
    {
        $this->discount_amount = $discount_amount;
        return $this;
    }

    public function getShippingFee(): ?string
    {
        return $this->shipping_fee;
    }

    public function setShippingFee(?string $shipping_fee): static
    {
        $this->shipping_fee = $shipping_fee;
        return $this;
    }

    public function getCardLast4(): ?string
    {
        return $this->card_last4;
    }

    public function setCardLast4(?string $card_last4): static
    {
        $this->card_last4 = $card_last4;
        return $this;
    }

    public function getCardBrand(): ?string
    {
        return $this->card_brand;
    }

    public function setCardBrand(?string $card_brand): static
    {
        $this->card_brand = $card_brand;
        return $this;
    }

    public function getCardName(): ?string
    {
        return $this->card_name;
    }

    public function setCardName(?string $card_name): static
    {
        $this->card_name = $card_name;
        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transaction_id;
    }

    public function setTransactionId(?string $transaction_id): static
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrder($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }
        }

        return $this;
    }
}
