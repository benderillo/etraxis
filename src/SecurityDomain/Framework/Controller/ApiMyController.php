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

use eTraxis\SecurityDomain\Model\Dictionary\AccountProvider;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

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
     * Returns profile of the current user.
     *
     * # Sample response
     * ```
     * {
     *     "id":       123,
     *     "email":    "anna@example.com",
     *     "fullname": "Anna Rodygina",
     *     "provider": "eTraxis",
     *     "locale":   "en_NZ",
     *     "theme":    "azure",
     *     "timezone": "Pacific/Auckland"
     * }
     * ```
     *
     * @Route("/profile", name="api_profile_get", methods={"GET"})
     *
     * @API\Response(response=200, description="Success.")
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
}
