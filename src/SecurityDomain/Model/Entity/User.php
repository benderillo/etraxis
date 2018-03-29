<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\SecurityDomain\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use eTraxis\SecurityDomain\Model\Dictionary\AccountProvider;
use LazySec\Entity\DisableAccountTrait;
use LazySec\Entity\LockAccountTrait;
use LazySec\Entity\UserTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Webinarium\PropertyTrait;

/**
 * User.
 *
 * @ORM\Table(
 *     name="users",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"account_provider", "account_uid"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\SecurityDomain\Model\Repository\UserRepository")
 * @Assert\UniqueEntity(fields={"email"}, message="user.conflict.email")
 *
 * @property-read int         $id          Unique ID.
 * @property      string      $email       Email address.
 * @property      string      $password    Password.
 * @property      string      $fullname    Full name.
 * @property      string      $description Optional description of the user.
 * @property      bool        $isAdmin     Whether the user has administrator privileges.
 * @property      AccountInfo $account     User's account.
 */
class User implements AdvancedUserInterface
{
    use PropertyTrait;
    use UserTrait;
    use DisableAccountTrait;
    use LockAccountTrait;

    // Roles.
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_USER  = 'ROLE_USER';

    // Constraints.
    public const MAX_EMAIL       = 254;
    public const MAX_FULLNAME    = 50;
    public const MAX_DESCRIPTION = 100;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=254, unique=true)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=60, nullable=true)
     */
    protected $password;

    /**
     * @var string
     *
     * @ORM\Column(name="fullname", type="string", length=50)
     */
    protected $fullname;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=100, nullable=true)
     */
    protected $description;

    /**
     * @var string User's role (see "User::ROLE_..." constants).
     *
     * @ORM\Column(name="role", type="string", length=20)
     */
    protected $role;

    /**
     * @var AccountInfo
     *
     * @ORM\Embedded(class="AccountInfo")
     */
    protected $account;

    /**
     * @var array User's settings.
     *
     * @ORM\Column(name="settings", type="json_array", nullable=true)
     */
    protected $settings;

    /**
     * Creates new user.
     */
    public function __construct()
    {
        $this->role    = self::ROLE_USER;
        $this->account = new AccountInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return [$this->role];
    }

    /**
     * Checks whether the account is loaded from 3rd party provider.
     *
     * @return bool
     */
    public function isAccountExternal(): bool
    {
        return $this->account->provider !== AccountProvider::ETRAXIS;
    }

    /**
     * {@inheritdoc}
     */
    protected function getters(): array
    {
        return [

            'isAdmin' => function (): bool {
                return $this->role === self::ROLE_ADMIN;
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setters(): array
    {
        return [

            'isAdmin' => function (bool $value): void {
                $this->role = $value ? self::ROLE_ADMIN : self::ROLE_USER;
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccountBeLocked(): bool
    {
        return !$this->isAccountExternal();
    }
}
