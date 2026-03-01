<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email = '';

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    #[Ignore]
    private string $password = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    private string $nom = '';

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(name: 'google2fa_secret', type: 'string', length: 255, nullable: true)]
    #[Ignore]
    private ?string $google2faSecret = null;

    #[ORM\Column(name: 'is_2fa_enabled', type: 'boolean')]
    private bool $is2faEnabled = false;

    #[ORM\Column(name: 'google_oauth_id', type: 'string', length: 255, nullable: true, unique: true)]
    private ?string $googleOAuthId = null;

    #[ORM\Column(name: 'oauth_provider', type: 'string', length: 50, nullable: true)]
    private ?string $oauthProvider = null;

    #[ORM\Column(name: 'face_encoding', type: 'text', nullable: true)]
    
    private ?string $faceEncoding = null;

    #[ORM\Column(name: 'is_face_enabled', type: 'boolean')]
    private bool $isFaceEnabled = false;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return (string) $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = trim($email);
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        return array_unique(array_merge($this->roles, ['ROLE_USER']));
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(#[\SensitiveParameter] string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function getNom(): string
    {
        return (string) $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = trim($nom);
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getGoogle2faSecret(): ?string
    {
        return $this->google2faSecret;
    }

    public function setGoogle2faSecret(#[\SensitiveParameter] ?string $google2faSecret): static
    {
        $this->google2faSecret = $google2faSecret;
        return $this;
    }

    public function is2faEnabled(): bool
    {
        return $this->is2faEnabled;
    }

    public function setIs2faEnabled(bool $is2faEnabled): static
    {
        $this->is2faEnabled = $is2faEnabled;
        return $this;
    }

    public function getGoogleOAuthId(): ?string
    {
        return $this->googleOAuthId;
    }

    public function setGoogleOAuthId(?string $googleOAuthId): static
    {
        $this->googleOAuthId = $googleOAuthId;
        return $this;
    }

    public function getOauthProvider(): ?string
    {
        return $this->oauthProvider;
    }

    public function setOauthProvider(?string $oauthProvider): static
    {
        $this->oauthProvider = $oauthProvider;
        return $this;
    }

    public function getFaceEncoding(): ?string
    {
        return $this->faceEncoding;
    }

    public function setFaceEncoding(?string $faceEncoding): static
    {
        $this->faceEncoding = $faceEncoding;
        return $this;
    }

    public function isFaceEnabled(): bool
    {
        return $this->isFaceEnabled;
    }

    public function setIsFaceEnabled(bool $isFaceEnabled): static
    {
        $this->isFaceEnabled = $isFaceEnabled;
        return $this;
    }
}
