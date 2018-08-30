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

use eTraxis\SecurityDomain\Application\Command\Users as Command;
use eTraxis\SecurityDomain\Model\Dictionary\AccountProvider;
use League\Tactician\CommandBus;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * API controller for '/my' resource.
 *
 * @Route("/api/my")
 *
 * @API\Tag(name="My Account")
 */
class ApiMyController extends Controller
{
    /**
     * Sets new password for the current user.
     *
     * @Route("/password", name="api_password_set", methods={"PUT"})
     *
     * @API\Parameter(name="current", in="formData", type="string", required=true, description="Current password (up to 4096 characters).")
     * @API\Parameter(name="new",     in="formData", type="string", required=true, description="New password (up to 4096 characters).")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="Wrong current password.<br>The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Password cannot be set for external accounts.")
     *
     * @param Request                      $request
     * @param CommandBus                   $commandBus
     * @param UserPasswordEncoderInterface $encoder
     * @param TranslatorInterface          $translator
     *
     * @return JsonResponse
     */
    public function setPassword(Request $request, CommandBus $commandBus, UserPasswordEncoderInterface $encoder, TranslatorInterface $translator): JsonResponse
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->getUser();

        if ($user->isAccountExternal()) {
            throw new AccessDeniedHttpException('Password cannot be set for external accounts.');
        }

        if (!$encoder->isPasswordValid($user, $request->request->get('current'))) {
            throw new BadRequestHttpException($translator->trans('Bad credentials.'));
        }

        $command = new Command\SetPasswordCommand([
            'user'     => $user->id,
            'password' => $request->request->get('new'),
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns profile of the current user.
     *
     * @Route("/profile", name="api_profile_get", methods={"GET"})
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",       type="integer", example=123),
     *         @API\Property(property="email",    type="string",  example="anna@example.com"),
     *         @API\Property(property="fullname", type="string",  example="Anna Rodygina"),
     *         @API\Property(property="provider", type="string",  example="eTraxis"),
     *         @API\Property(property="locale",   type="string",  example="en_NZ"),
     *         @API\Property(property="theme",    type="string",  example="azure"),
     *         @API\Property(property="timezone", type="string",  example="Pacific/Auckland")
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @return JsonResponse
     */
    public function getProfile(): JsonResponse
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->getUser();

        return $this->json([
            'id'       => $user->id,
            'email'    => $user->email,
            'fullname' => $user->fullname,
            'provider' => AccountProvider::get($user->account->provider),
        ]);
    }

    /**
     * Updates profile of the current user.
     *
     * @Route("/profile", name="api_profile_update", methods={"PATCH"})
     *
     * @API\Parameter(name="email",    in="formData", type="string", required=false, description="Email address (RFC 5322). Ignored for external accounts.")
     * @API\Parameter(name="fullname", in="formData", type="string", required=false, description="Full name (up to 50 characters). Ignored for external accounts.")
     * @API\Parameter(name="locale",   in="formData", type="string", required=false, description="Locale ('xx' or 'xx_XX', see ISO 639-1 / ISO 3166-1).")
     * @API\Parameter(name="theme",    in="formData", type="string", required=false, description="Theme.")
     * @API\Parameter(name="timezone", in="formData", type="string", required=false, description="Timezone (IANA database value).")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=409, description="Account with specified email already exists.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function updateProfile(Request $request, CommandBus $commandBus): JsonResponse
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->getUser();

        $profile = new Command\UpdateProfileCommand([
            'email'    => $request->request->get('email', $user->email),
            'fullname' => $request->request->get('fullname', $user->fullname),
        ]);

        $settings = new Command\UpdateSettingsCommand([
            'locale'   => $request->request->get('locale', $user->locale),
            'theme'    => $request->request->get('theme', $user->theme),
            'timezone' => $request->request->get('timezone', $user->timezone),
        ]);

        if (!$user->isAccountExternal()) {
            $commandBus->handle($profile);
        }

        $commandBus->handle($settings);

        return $this->json(null);
    }
}
