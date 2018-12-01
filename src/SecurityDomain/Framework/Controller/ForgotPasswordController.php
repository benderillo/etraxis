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

use eTraxis\SecurityDomain\Application\Command\Users\ForgetPasswordCommand;
use League\Tactician\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Forgot password controller.
 *
 * @Route("/forgot")
 */
class ForgotPasswordController extends AbstractController
{
    /**
     * 'Forgot password' page.
     *
     * @Route(name="forgot_password", methods={"GET"})
     *
     * @return Response
     */
    public function index(): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('security/forgot/index.html.twig');
    }

    /**
     * Generates a reset token for forgotten password.
     *
     * @Route(methods={"POST"})
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function forgotPassword(Request $request, CommandBus $commandBus): JsonResponse
    {
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {

            $command = new ForgetPasswordCommand($request->request->all());

            $commandBus->handle($command);
        }

        return $this->json(null);
    }
}
