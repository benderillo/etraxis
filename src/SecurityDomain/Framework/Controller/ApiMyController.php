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
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\TemplatesDomain\Model\Repository\TemplateRepository;
use League\Tactician\CommandBus;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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
 * @Security("has_role('ROLE_USER')")
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
     * @API\Parameter(name="", in="body", @API\Schema(
     *     type="object",
     *     required={"current", "new"},
     *     properties={
     *         @API\Property(property="current", type="string", maxLength=4096, description="Current password."),
     *         @API\Property(property="new",     type="string", maxLength=4096, description="New password.")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="Wrong current password, or The request is malformed.")
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
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\SecurityDomain\Model\API\Profile::class))
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
            'provider' => $user->account->provider,
            'locale'   => $user->locale,
            'theme'    => $user->theme,
            'timezone' => $user->timezone,
        ]);
    }

    /**
     * Updates profile of the current user.
     *
     * @Route("/profile", name="api_profile_update", methods={"PATCH"})
     *
     * @API\Parameter(name="", in="body", @API\Schema(
     *     type="object",
     *     required={},
     *     properties={
     *         @API\Property(property="email",    type="string", maxLength=254, description="Email address (RFC 5322). Ignored for external accounts."),
     *         @API\Property(property="fullname", type="string", maxLength=50, description="Full name. Ignored for external accounts."),
     *         @API\Property(property="locale",   type="string", example="en_NZ", description="Locale (ISO 639-1 / ISO 3166-1)."),
     *         @API\Property(property="theme",    type="string", enum={"azure", "emerald", "humanity", "mars"}, example="azure", description="Theme."),
     *         @API\Property(property="timezone", type="string", example="Pacific/Auckland", description="Timezone (IANA database value).")
     *     }
     * ))
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

    /**
     * Returns list of projects which can be used to create new issue.
     *
     * @Route("/projects", name="api_profile_projects", methods={"GET"})
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\TemplatesDomain\Model\API\Project::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param TemplateRepository $repository
     *
     * @return JsonResponse
     */
    public function getProjects(TemplateRepository $repository): JsonResponse
    {
        $projects = array_map(function (Template $template) {
            return $template->project;
        }, $repository->getTemplatesByUser($this->getUser()));

        return $this->json(array_values(array_unique($projects, SORT_REGULAR)));
    }

    /**
     * Returns list of templates which can be used to create new issue.
     *
     * @Route("/templates", name="api_profile_templates", methods={"GET"})
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\TemplatesDomain\Model\API\Template::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param TemplateRepository $repository
     *
     * @return JsonResponse
     */
    public function getTemplates(TemplateRepository $repository): JsonResponse
    {
        return $this->json($repository->getTemplatesByUser($this->getUser()));
    }
}
