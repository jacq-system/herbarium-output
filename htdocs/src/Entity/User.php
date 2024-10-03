<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\OneToMany(targetEntity: OAuth2UserConsent::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $oAuth2UserConsents;

    public function __construct()
    {
        $this->oAuth2UserConsents = new ArrayCollection();
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, OAuth2UserConsent>
     */
    public function getOAuth2UserConsents(): Collection
    {
        return $this->oAuth2UserConsents;
    }

    public function addOAuth2UserConsent(OAuth2UserConsent $oAuth2UserConsent): static
    {
        if (!$this->oAuth2UserConsents->contains($oAuth2UserConsent)) {
            $this->oAuth2UserConsents->add($oAuth2UserConsent);
            $oAuth2UserConsent->setUser($this);
        }

        return $this;
    }

    public function removeOAuth2UserConsent(OAuth2UserConsent $oAuth2UserConsent): static
    {
        if ($this->oAuth2UserConsents->removeElement($oAuth2UserConsent)) {
            // set the owning side to null (unless already changed)
            if ($oAuth2UserConsent->getUser() === $this) {
                $oAuth2UserConsent->setUser(null);
            }
        }

        return $this;
    }
}
