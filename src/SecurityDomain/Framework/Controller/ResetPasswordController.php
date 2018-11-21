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

use eTraxis\SecurityDomain\Application\Command\Users\ResetPasswordCommand;
use League\Tactician\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Reset password controller.
 *
 * @Route("/reset/{token}")
 */
class ResetPasswordController extends Controller
{
    /**
     * 'Reset password' page.
     *
     * @Route(name="reset_password", methods={"GET"})
     *
     * @param string $token
     *
     * @return Response
     */
    public function index(string $token): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('security/reset/index.html.twig', [
            'token' => $token,
        ]);
    }

    /**
     * Resets a forgotten password by specified token.
     *
     * @Route(methods={"POST"})
     *
     * @param Request    $request
     * @param string     $token
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function resetPassword(Request $request, string $token, CommandBus $commandBus): JsonResponse
    {
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {

            $command = new ResetPasswordCommand($request->request->all());

            $command->token = $token;

            try {
                $commandBus->handle($command);
            }
            catch (NotFoundHttpException $exception) {
                return $this->json(null);
            }
        }

        return $this->json(null);
    }
}
