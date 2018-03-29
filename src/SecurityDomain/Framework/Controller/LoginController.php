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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Login controller.
 */
class LoginController extends Controller
{
    /**
     * Login page.
     *
     * @Route("/login", name="login")
     *
     * @param AuthenticationUtils $utils
     *
     * @return Response
     */
    public function index(AuthenticationUtils $utils): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('security/login.html.twig', [
            'error'    => $utils->getLastAuthenticationError(),
            'username' => $utils->getLastUsername(),
        ]);
    }
}
