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
    /**
     * Returns list of users.
     *
     * @Route("", name="api_users_list", methods={"GET"})
     *
     * @API\Parameter(name="offset",   in="query", type="integer", required=false, description="Zero-based index of the first user to return.")
     * @API\Parameter(name="limit",    in="query", type="integer", required=false, description="Maximum number of users to return (1 - 100).")
     * @API\Parameter(name="X-Search", in="body",  type="string",  required=false, description="Optional search value.", @API\Schema(type="string", example="whatever"))
     * @API\Parameter(name="X-Filter", in="body",  type="object",  required=false, description="Optional filters.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="email",       type="string",  example="example.com"),
     *         @API\Property(property="fullname",    type="string",  example="Smith"),
     *         @API\Property(property="description", type="string",  example="client"),
     *         @API\Property(property="admin",       type="boolean", example=false),
     *         @API\Property(property="disabled",    type="boolean", example=true),
     *         @API\Property(property="locked",      type="boolean", example=true),
     *         @API\Property(property="provider",    type="string",  example="LDAP")
     *     }
     * ))
     * @API\Parameter(name="X-Sort", in="body", type="object", required=false, description="Optional sorting.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",          type="string", example="ASC"),
     *         @API\Property(property="email",       type="string", example="ASC"),
     *         @API\Property(property="fullname",    type="string", example="ASC"),
     *         @API\Property(property="description", type="string", example="ASC"),
     *         @API\Property(property="admin",       type="string", example="ASC"),
     *         @API\Property(property="provider",    type="string", example="ASC")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         type="object",
     *         properties={
     *             @API\Property(property="id",          type="integer", example=123),
     *             @API\Property(property="email",       type="string",  example="anna@example.com"),
     *             @API\Property(property="fullname",    type="string",  example="Anna Rodygina"),
     *             @API\Property(property="description", type="string",  example="very lovely daughter"),
     *             @API\Property(property="admin",       type="boolean", example=false),
     *             @API\Property(property="disabled",    type="boolean", example=false),
     *             @API\Property(property="locked",      type="boolean", example=false),
     *             @API\Property(property="provider",    type="string",  example="eTraxis"),
     *             @API\Property(property="locale",      type="string",  example="en_NZ"),
     *             @API\Property(property="theme",       type="string",  example="azure"),
     *             @API\Property(property="timezone",    type="string",  example="Pacific/Auckland")
     *         }
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
        $offset = (int) $request->get('offset', 0);
        $limit  = (int) $request->get('limit', UserRepository::MAX_LIMIT);

        $offset = max(0, $offset);
        $limit  = max(1, min($limit, UserRepository::MAX_LIMIT));

        $search = $request->headers->get('X-Search');
        $filter = json_decode($request->headers->get('X-Filter'), true);
        $sort   = json_decode($request->headers->get('X-Sort'), true);

        $collection = $repository->getCollection($offset, $limit, $search, $filter ?? [], $sort ?? []);

        return $this->json($collection);
    }
}
