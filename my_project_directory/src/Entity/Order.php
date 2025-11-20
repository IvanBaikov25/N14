<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Клиент обязателен")]
    private Client $client;

    #[ORM\ManyToMany(targetEntity: Dish::class)]
    #[Assert\Count(min: 1, minMessage: "В заказе должно быть хотя бы одно блюдо")]
    private Collection $dishes;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->dishes = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getClient(): Client
    {
        return $this->client;
    }
    public function setClient(Client $client): self
    {
        $this->client = $client;
        return $this;
    }
    public function getDishes(): Collection
    {
        return $this->dishes;
    }
    public function addDish(Dish $dish): self
    {
        if (!$this->dishes->contains($dish)) {
            $this->dishes[] = $dish;
        }
        return $this;
    }
    public function removeDish(Dish $dish): self
    {
        $this->dishes->removeElement($dish);
        return $this;
    }
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}