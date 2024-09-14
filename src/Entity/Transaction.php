<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{

    public const MAXPERPAGE = 10;
    
    public const CATEGORIES = [
        1 => "Investissement",
        2 => "Dépot",
        3 => "Retrait",
        4 => "Dividende"
    ];

    public const INVESTMENT_CATEGORY = 1;
    public const DEPOSIT_CATEGORY = 2;
    public const WITHDRAW_CATEGORY = 3;
    public const DIVIDEND_CATEGORY = 4;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column]
    private ?int $category = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    private ?Project $project = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'dividends')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $investment = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'investment')]
    private Collection $dividends;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: "bank_account_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?BankAccount $bankAccount = null;

    public function __construct()
    {
        $this->dividends = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getCategory(): ?int
    {
        return $this->category;
    }

    public function setCategory(int $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

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

    public function getInvestment(): ?self
    {
        return $this->investment;
    }

    public function setInvestment(?self $investment): static
    {
        $this->investment = $investment;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getDividends(): Collection
    {
        return $this->dividends;
    }

    public function addDividend(self $dividend): static
    {
        if (!$this->dividends->contains($dividend)) {
            $this->dividends->add($dividend);
            $dividend->setInvestment($this);
        }

        return $this;
    }

    public function removeDividend(self $dividend): static
    {
        if ($this->dividends->removeElement($dividend)) {
            // set the owning side to null (unless already changed)
            if ($dividend->getInvestment() === $this) {
                $dividend->setInvestment(null);
            }
        }

        return $this;
    }

    public function getBankAccount(): ?BankAccount
    {
        return $this->bankAccount;
    }

    public function setBankAccount(?BankAccount $bankAccount): static
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }
}
