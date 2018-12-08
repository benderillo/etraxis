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

namespace eTraxis\SecurityDomain\Framework\Controller;

use eTraxis\SecurityDomain\Application\Voter\UserVoter;
use eTraxis\SecurityDomain\Model\Dictionary\AccountProvider;
use eTraxis\SecurityDomain\Model\Dictionary\Locale;
use eTraxis\SecurityDomain\Model\Dictionary\Timezone;
use eTraxis\SecurityDomain\Model\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Users controller.
 *
 * @Route("/admin/users")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class UsersController extends AbstractController
{
    /**
     * 'Users' page.
     *
     * @Route("", name="admin_users", methods={"GET"})
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->render('security/users/index.html.twig');
    }

    /**
     * A user page.
     *
     * @Route("/{id}", name="admin_view_user", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @param User $user
     *
     * @return Response
     */
    public function view(User $user): Response
    {
        return $this->render('security/users/view.html.twig', [
            'user'      => $user,
            'providers' => AccountProvider::all(),
            'locales'   => Locale::all(),
            'timezones' => Timezone::all(),
            'can'       => [
                'delete'  => $this->isGranted(UserVoter::DELETE_USER, $user),
                'disable' => $this->isGranted(UserVoter::DISABLE_USER, $user),
                'enable'  => $this->isGranted(UserVoter::ENABLE_USER, $user),
                'unlock'  => $this->isGranted(UserVoter::UNLOCK_USER, $user),
            ],
        ]);
    }
}
