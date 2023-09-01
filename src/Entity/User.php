<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="utilisateurs")
 * @UniqueEntity(fields={"username"}, message="un compte avec ce nom d'utilisateur existe déjà")
 * @UniqueEntity(fields={"email"}, message="Un compte avec cet email existe déjà")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Get role names
     * @return array
     */
    public static function getRoleNames(){
        /*
         * WARNING : L'ordre est important (du plus faible au plus fort)
         */
        return array(
                'ROLE_USER'   => 'UTILISATEUR',
                'ROLE_EDUGEO_ELEVE' => 'ELEVE',
                'ROLE_EDUGEO_PROF' => 'PROFESSEUR',
                'ROLE_EDITOR' => 'EDITEUR',
                'ROLE_SUPER_ADMIN' => 'SUPER ADMIN'
        ) ;
    }

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="username", type="string", length=180, unique=true)
     */
    private $username;
    
    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(name="roles", type="array")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(name="password", type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $salt;

    /**
     * @ORM\Column(name="locked", type="boolean")
     */
    private $locked;

    /**
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    private $lastLogin;

    /**
     * @ORM\Column(name="registered_at", type="datetime", nullable=true)
     */
    private $registeredAt;

    /* ------------------ PROFIL PUBLIC --------------------- */ 

    /**
     * Nom affiché sur les parties publiques
     * @ORM\Column(name="public_name", type="string", length=255, unique=true)
     */
    private $publicName;

    /**
     * @ORM\Column(type="string", length=4, nullable=true)
     */
    private $publicId;

    /**
     * Présentation de l'utilisateur
     * @ORM\Column(name="introduction", type="text", nullable=true)
     */
    private $presentation;

    /**
     * @ORM\Column(name="twitter_account", type="string", length=255, nullable=true)
     */
    private $twitterAccount;

    /**
     * @ORM\Column(name="facebook_account", type="string", length=255, nullable=true)
     */
    private $facebookAccount;

    /**
     * @ORM\Column(name="linkedin_account", type="string", length=255, nullable=true)
     */
    private $linkedinAccount;

    /**
     * @ORM\Column(name="profile_picture", type="string", length=255, nullable=true)
     */
    private $profilePicture;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $coverPicture;

    /* ------------------ RELATIONS --------------------- */ 

    /**
     * @ORM\OneToMany(targetEntity=Map::class, mappedBy="creator", cascade={"remove"})
     */
    private $maps;

    /**
     * @ORM\OneToMany(targetEntity=Media::class, mappedBy="owner", orphanRemoval=true)
     */
    private $medias;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $confirmationToken;

    /**
     * @ORM\OneToMany(targetEntity=Article::class, mappedBy="updatedBy")
     */
    private $articles;

    /**
     * @ORM\Column(type="integer")
     */
    private $loginAttempt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $loginType;

    /**
     * @ORM\OneToMany(targetEntity=Notification::class, mappedBy="updatedBy")
     */
    private $notifications;

    public function __construct()
    {
        $this->maps = new ArrayCollection();
        $this->medias = new ArrayCollection();
        $this->locked = false;
        $this->enabled = false;
        $this->registeredAt = new DateTime();
        $this->articles = new ArrayCollection();
        $this->loginAttempt = 0;
        $this->notifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    public function getPublicName(): ?string
    {
        return $this->publicName;
    }

    public function setPublicName(?string $publicName): self
    {
        $this->publicName = $publicName;

        return $this;
    }

    public function getPresentation(): ?string
    {
        return $this->presentation;
    }

    public function setPresentation(?string $presentation): self
    {
        $this->presentation = $presentation;

        return $this;
    }

    public function getTwitterAccount(): ?string
    {
        return $this->twitterAccount;
    }

    public function setTwitterAccount(?string $twitterAccount): self
    {
        $this->twitterAccount = $twitterAccount;

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): self
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    /**
     * @return Collection|Map[]
     */
    public function getMaps(): Collection
    {
        return $this->maps;
    }

    public function addMap(Map $map): self
    {
        if (!$this->maps->contains($map)) {
            $this->maps[] = $map;
            $map->setCreator($this);
        }

        return $this;
    }

    public function removeMap(Map $map): self
    {
        if ($this->maps->removeElement($map)) {
            // set the owning side to null (unless already changed)
            if ($map->getCreator() === $this) {
                $map->setCreator(null);
            }
        }

        return $this;
    }

    public function hasSharedMaps(): bool
    {
        foreach($this->getMaps() as $map){
            if($map->getShare() == Map::SHARE_ATLAS){
                return true;
            }
        }
        return false;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function setSalt(string $salt): self
    {
        $this->salt = $salt;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return Collection|Media[]
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(Media $media): self
    {
        if (!$this->medias->contains($media)) {
            $this->medias[] = $media;
            $media->setOwner($this);
        }

        return $this;
    }

    public function removeMedia(Media $media): self
    {
        if ($this->medias->removeElement($media)) {
            // set the owning side to null (unless already changed)
            if ($media->getOwner() === $this) {
                $media->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * Renvoie la taille globale des medias chargés en octet
     * @return int
     */
    public function getMediasSize(){
        $size = 0;
        foreach($this->getMedias() as $media){
            $size += $media->getSize();
        }
        
        return $size;
    }

    /**
     * Renvoie la taille globale maximale des medias chargés autorisée en octet
     * @return int
     */
    public function getMediasizeLimit():int {
        
        if($this->hasRole('ROLE_SUPER_ADMIN')){
            return 100*1024*1024; //100Mo
        }
        
        //par défaut
        return 10*1024*1024; //10Mo
    }

    public function getFacebookAccount(): ?string
    {
        return $this->facebookAccount;
    }

    public function setFacebookAccount(?string $facebookAccount): self
    {
        $this->facebookAccount = $facebookAccount;

        return $this;
    }

    public function getLinkedinAccount(): ?string
    {
        return $this->linkedinAccount;
    }

    public function setLinkedinAccount(?string $linkedinAccount): self
    {
        $this->linkedinAccount = $linkedinAccount;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): self
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function getCoverPicture(): ?string
    {
        return $this->coverPicture;
    }

    public function setCoverPicture(?string $coverPicture): self
    {
        $this->coverPicture = $coverPicture;

        return $this;
    }

    public function getRegisteredAt(): ?\DateTimeInterface
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(?\DateTimeInterface $registeredAt): self
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles[] = $article;
            $article->setUpdatedBy($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): self
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getUpdatedBy() === $this) {
                $article->setUpdatedBy(null);
            }
        }

        return $this;
    }

    public function getLoginAttempt(): ?int
    {
        return $this->loginAttempt;
    }

    public function setLoginAttempt(int $loginAttempt): self
    {
        if($loginAttempt < 0){
            $loginAttempt = 0;
        }
        $this->loginAttempt = $loginAttempt;

        return $this;
    }

    public function getLoginType(): ?string
    {
        return $this->loginType;
    }

    public function setLoginType(?string $loginType): self
    {
        $this->loginType = $loginType;

        return $this;
    }

    public function getPublicId(): ?string
    {
        return $this->publicId;
    }

    public function setPublicId(?string $publicId): self
    {
        $this->publicId = $publicId;

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setUpdatedBy($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUpdatedBy() === $this) {
                $notification->setUpdatedBy(null);
            }
        }

        return $this;
    }
    
}
