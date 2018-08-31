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

use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use eTraxis\SharedDomain\Model\Collection\CollectionTrait;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API controller for '/users' resource.
 *
 * @Route("/api/users")
 * @Security("has_role('ROLE_ADMIN')")
 *
 * @API\Tag(name="Users")
 */
class ApiUsersController extends Controller
{
    use CollectionTrait;

    /**
     * Returns list of users.
     *
     * @Route("", name="api_users_list", methods={"GET"})
     *
     * @API\Parameter(name="offset",   in="query", type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first user to return.")
     * @API\Parameter(name="limit",    in="query", type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of users to return.")
     * @API\Parameter(name="X-Search", in="body",  type="string",  required=false, description="Optional search value.", @API\Schema(type="string"))
     * @API\Parameter(name="X-Filter", in="body",  type="object",  required=false, description="Optional filters.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="email",       type="string"),
     *         @API\Property(property="fullname",    type="string"),
     *         @API\Property(property="description", type="string"),
     *         @API\Property(property="admin",       type="boolean"),
     *         @API\Property(property="disabled",    type="boolean"),
     *         @API\Property(property="locked",      type="boolean"),
     *         @API\Property(property="provider",    type="string", enum={"eTraxis", "LDAP"}, example="LDAP")
     *     }
     * ))
     * @API\Parameter(name="X-Sort", in="body", type="object", required=false, description="Optional sorting.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",          type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="email",       type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="fullname",    type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="description", type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="admin",       type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="provider",    type="string", enum={"ASC", "DESC"}, example="ASC")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\SecurityDomain\Model\API\User::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     *
     * @param Request        $request
     * @param UserRepository $repository
     *
     * @return JsonResponse
     */
    public function listUsers(Request $request, UserRepository $repository): JsonResponse
    {
        $collection = $this->getCollection($request, $repository);

        return $this->json($collection);
    }
}
