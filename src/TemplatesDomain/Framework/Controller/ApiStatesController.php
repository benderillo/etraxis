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

namespace eTraxis\TemplatesDomain\Framework\Controller;

use eTraxis\SharedDomain\Model\Collection\CollectionTrait;
use eTraxis\TemplatesDomain\Application\Command\States as Command;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Repository\StateRepository;
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
 * API controller for '/states' resource.
 *
 * @Route("/api/states")
 * @Security("has_role('ROLE_ADMIN')")
 *
 * @API\Tag(name="States")
 */
class ApiStatesController extends Controller
{
    use CollectionTrait;

    /**
     * Returns list of states.
     *
     * @Route("", name="api_states_list", methods={"GET"})
     *
     * @API\Parameter(name="offset",   in="query", type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first state to return.")
     * @API\Parameter(name="limit",    in="query", type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of states to return.")
     * @API\Parameter(name="X-Search", in="body",  type="string",  required=false, description="Optional search value.", @API\Schema(type="string"))
     * @API\Parameter(name="X-Filter", in="body",  type="object",  required=false, description="Optional filters.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="project",     type="integer"),
     *         @API\Property(property="template",    type="integer"),
     *         @API\Property(property="name",        type="string"),
     *         @API\Property(property="type",        type="string"),
     *         @API\Property(property="responsible", type="string")
     *     }
     * ))
     * @API\Parameter(name="X-Sort", in="body", type="object", required=false, description="Optional sorting.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",          type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="project",     type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="template",    type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="name",        type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="type",        type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="responsible", type="string", enum={"ASC", "DESC"}, example="ASC")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned state."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned state."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found states."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\TemplatesDomain\Model\API\State::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     *
     * @param Request         $request
     * @param StateRepository $repository
     *
     * @return JsonResponse
     */
    public function listStates(Request $request, StateRepository $repository): JsonResponse
    {
        $collection = $this->getCollection($request, $repository);

        return $this->json($collection);
    }

    /**
     * Creates new state.
     *
     * @Route("", name="api_states_create", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\CreateStateCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Template is not found.")
     * @API\Response(response=409, description="State with specified name already exists.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function createState(Request $request, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\CreateStateCommand($request->request->all());

        /** @var State $state */
        $state = $commandBus->handle($command);

        $url = $this->generateUrl('api_states_get', [
            'id' => $state->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified state.
     *
     * @Route("/{id}", name="api_states_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="State ID.")
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\TemplatesDomain\Model\API\State::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="State is not found.")
     *
     * @param State $state
     *
     * @return JsonResponse
     */
    public function getState(State $state): JsonResponse
    {
        return $this->json($state);
    }

    /**
     * Updates specified state.
     *
     * @Route("/{id}", name="api_states_update", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="State ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\UpdateStateCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="State is not found.")
     * @API\Response(response=409, description="State with specified name already exists.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function updateState(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\UpdateStateCommand($request->request->all());

        $command->state = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified state.
     *
     * @Route("/{id}", name="api_states_delete", methods={"DELETE"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="State ID.")
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
    public function deleteState(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\DeleteStateCommand([
            'state' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Sets specified state as initial.
     *
     * @Route("/{id}/initial", name="api_states_initial", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="State ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="State is not found.")
     *
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function setInitialState(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\SetInitialStateCommand([
            'state' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }
}
