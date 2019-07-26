<?php

namespace App\Entity;

use App\Types\RoleType;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * User
 *
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"read"}},
 *     "denormalization_context"={"groups"={"write"}},
 *     "validation_groups"={"App\Entity\User", "validationGroups"},
 *     "filters"={"user.search"}
 * })
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="user_login_unique", columns={"login"}),
 * })
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity("login")
 */
class User implements UserInterface
{
    /**
     * @var int
     *
     * @Groups({"read"})
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @Assert\Length(min=4, max=100)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=20, nullable=false)
     */
    private $login;

    /**
     * @var string
     *
     * @Groups({"read", "write", "details"})
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string|null
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $phone;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=20, nullable=false)
     */
    private $userLevel;

    /**
     * @var bool
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $active;

    /**
     * @var \DateTime|null
     *
     * @Groups({"read"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastLoginDate;

    /**
     * @var string
     *
     * @Assert\NotBlank(groups={"create"})
     * @Assert\Length(min=8, max=20)
     * @Assert\Regex(
     *     pattern="/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[?!\-_+:@])[a-zA-Z0-9\S]{8,20}$/",
     *     message="This value doesn't respect the password policy. The password has to contain at least one small letter, one capital letter, one number and one special character (? ! - _ + : @)."
     * )
     * @Groups({"write"})
     */
    private $plainPassword;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=128, nullable=false)
     */
    private $password;

    /**
     * @var \DateTime|null
     *
     * @Assert\Type("\DateTime")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastPasswordChangedAt;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getLogin() : string
    {
        return $this->login;
    }

    public function setLogin(string $login) : self
    {
        $this->login = $login;

        return $this;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : self
    {
        $this->name = $name;

        return $this;
    }

    public function getPhone() : ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone) : self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getUserLevel() : string
    {
        return $this->userLevel;
    }

    public function setUserLevel(string $userLevel) : self
    {
        $this->userLevel = $userLevel;

        return $this;
    }

    public function getActive() : ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active) : self
    {
        $this->active = $active;

        return $this;
    }

    public function getLastLoginDate() : ?\DateTime
    {
        return $this->lastLoginDate;
    }

    public function setLastLoginDate(?\DateTime $lastLoginDate) : self
    {
        $this->lastLoginDate = $lastLoginDate;

        return $this;
    }

    public function getPassword() : ?string
    {
        return $this->password;
    }

    public function setPassword(string $password) : self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword() : ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword) : self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getLastPasswordChangedAt() : ?\DateTime
    {
        return $this->lastPasswordChangedAt;
    }

    public function setLastPasswordChangedAt(?\DateTime $lastPasswordChangedAt) : self
    {
        $this->lastPasswordChangedAt = $lastPasswordChangedAt;

        return $this;
    }

    /**
     * @see \Symfony\Component\Security\Core\User\UserInterface::getRoles()
     */
    public function getRoles()
    {
        return [RoleType::getRoleForLevel($this->getUserlevel())];
    }

    /**
     * @see \Symfony\Component\Security\Core\User\UserInterface::getSalt()
     */
    public function getSalt()
    {
        return '';
    }

    /**
     * @see \Symfony\Component\Security\Core\User\UserInterface::getUsername()
     */
    public function getUsername()
    {
        return $this->login;
    }

    /**
     * @see \Symfony\Component\Security\Core\User\UserInterface::eraseCredentials()
     */
    public function eraseCredentials()
    {
    }

    /**
     * Return dynamic validation groups.
     *
     * @return string[]
     */
    public static function validationGroups(self $user)
    {
        if (null === $user->id) {
            return ['Default', 'create'];
        }
    }
}
