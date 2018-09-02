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

use eTraxis\SecurityDomain\Application\Command\Groups as Command;
use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\SecurityDomain\Model\Repository\GroupRepository;
use eTraxis\SharedDomain\Model\Collection\CollectionTrait;
use League\Tactician\CommandBus;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * API controller for '/groups' resource.
 *
 * @Route("/api/groups")
 * @Security("has_role('ROLE_ADMIN')")
 *
 * @API\Tag(name="Groups")
 */
class ApiGroupsController extends Controller
{
    use CollectionTrait;

    /**
     * Returns list of groups.
     *
     * @Route("", name="api_groups_list", methods={"GET"})
     *
     * @API\Parameter(name="offset",   in="query", type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first group to return.")
     * @API\Parameter(name="limit",    in="query", type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of groups to return.")
     * @API\Parameter(name="X-Search", in="body",  type="string",  required=false, description="Optional search value.", @API\Schema(type="string"))
     * @API\Parameter(name="X-Filter", in="body",  type="object",  required=false, description="Optional filters.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="project",     type="integer"),
     *         @API\Property(property="name",        type="string"),
     *         @API\Property(property="description", type="string"),
     *         @API\Property(property="global",      type="boolean")
     *     }
     * ))
     * @API\Parameter(name="X-Sort", in="body", type="object", required=false, description="Optional sorting.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",          type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="project",     type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="name",        type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="description", type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="global",      type="string", enum={"ASC", "DESC"}, example="ASC")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\SecurityDomain\Model\API\Group::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     *
     * @param Request         $request
     * @param GroupRepository $repository
     *
     * @return JsonResponse
     */
    public function listGroups(Request $request, GroupRepository $repository): JsonResponse
    {
        $collection = $this->getCollection($request, $repository);

        return $this->json($collection);
    }

    /**
     * Creates new group.
     *
     * @Route("", name="api_groups_create", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\CreateGroupCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Project is not found.")
     * @API\Response(response=409, description="Group with specified name already exists.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function createGroup(Request $request, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\CreateGroupCommand($request->request->all());

        /** @var Group $group */
        $group = $commandBus->handle($command);

        $url = $this->generateUrl('api_groups_get', [
            'id' => $group->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified group.
     *
     * @Route("/{id}", name="api_groups_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Group ID.")
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\SecurityDomain\Model\API\Group::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Group is not found.")
     *
     * @param Group $group
     *
     * @return JsonResponse
     */
    public function getGroup(Group $group): JsonResponse
    {
        return $this->json($group);
    }

    /**
     * Updates specified group.
     *
     * @Route("/{id}", name="api_groups_update", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Group ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\UpdateGroupCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Group is not found.")
     * @API\Response(response=409, description="Group with specified name already exists.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function updateGroup(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\UpdateGroupCommand($request->request->all());

        $command->group = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified group.
     *
     * @Route("/{id}", name="api_groups_delete", methods={"DELETE"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Group ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     *
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function deleteGroup(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\DeleteGroupCommand([
            'group' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }
}
