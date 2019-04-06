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

namespace eTraxis\SecurityDomain\Model\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use eTraxis\SecurityDomain\Model\Dictionary\Timezone;
use eTraxis\SecurityDomain\Model\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Fixtures for first-time deployment to production.
 */
class ProductionFixtures extends Fixture
{
    protected $encoder;
    protected $locale;
    protected $theme;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param UserPasswordEncoderInterface $encoder
     * @param string                       $locale
     * @param string                       $theme
     */
    public function __construct(UserPasswordEncoderInterface $encoder, string $locale, string $theme)
    {
        $this->encoder = $encoder;
        $this->locale  = $locale;
        $this->theme   = $theme;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = new User();

        $user->email       = 'admin@example.com';
        $user->password    = $this->encoder->encodePassword($user, 'secret');
        $user->fullname    = 'eTraxis Admin';
        $user->description = 'Built-in administrator';
        $user->isAdmin     = true;
        $user->locale      = $this->locale;
        $user->theme       = $this->theme;
        $user->timezone    = Timezone::FALLBACK;

        $manager->persist($user);
        $manager->flush();
    }
}
