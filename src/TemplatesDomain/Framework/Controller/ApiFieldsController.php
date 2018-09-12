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
use eTraxis\TemplatesDomain\Application\Command\Fields as Command;
use eTraxis\TemplatesDomain\Application\Command\ListItems\CreateListItemCommand;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Repository\FieldRepository;
use eTraxis\TemplatesDomain\Model\Repository\ListItemRepository;
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
 * API controller for '/fields' resource.
 *
 * @Route("/api/fields")
 * @Security("has_role('ROLE_ADMIN')")
 *
 * @API\Tag(name="Fields")
 */
class ApiFieldsController extends Controller
{
    use CollectionTrait;

    /**
     * Returns list of fields.
     *
     * @Route("", name="api_fields_list", methods={"GET"})
     *
     * @API\Parameter(name="offset",   in="query", type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first field to return.")
     * @API\Parameter(name="limit",    in="query", type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of fields to return.")
     * @API\Parameter(name="X-Search", in="body",  type="string",  required=false, description="Optional search value.", @API\Schema(type="string"))
     * @API\Parameter(name="X-Filter", in="body",  type="object",  required=false, description="Optional filters.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="project",     type="integer"),
     *         @API\Property(property="template",    type="integer"),
     *         @API\Property(property="state",       type="integer"),
     *         @API\Property(property="name",        type="string"),
     *         @API\Property(property="type",        type="string"),
     *         @API\Property(property="description", type="string"),
     *         @API\Property(property="position",    type="integer"),
     *         @API\Property(property="required",    type="boolean")
     *     }
     * ))
     * @API\Parameter(name="X-Sort", in="body", type="object", required=false, description="Optional sorting.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",          type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="project",     type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="template",    type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="state",       type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="name",        type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="type",        type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="description", type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="position",    type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="required",    type="string", enum={"ASC", "DESC"}, example="ASC")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned field."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned field."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found fields."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\TemplatesDomain\Model\API\Field::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     *
     * @param Request         $request
     * @param FieldRepository $repository
     *
     * @return JsonResponse
     */
    public function listFields(Request $request, FieldRepository $repository): JsonResponse
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->getDoctrine()->getManager();

        $collection = $this->getCollection($request, $repository);

        $data = array_map(function (Field $field) use ($manager) {
            $main  = $field->jsonSerialize();
            $extra = $field->getFacade($manager)->jsonSerialize();

            return $main + $extra;
        }, $collection->data);

        $collection->data = $data;

        return $this->json($collection);
    }

    /**
     * Creates new field.
     *
     * @Route("", name="api_fields_create", methods={"POST"})
     *
     * @API\Parameter(name="checkbox", in="body", @Model(type=Command\CreateCheckboxFieldCommand::class, groups={"api"}))
     * @API\Parameter(name="date",     in="body", @Model(type=Command\CreateDateFieldCommand::class,     groups={"api"}))
     * @API\Parameter(name="decimal",  in="body", @Model(type=Command\CreateDecimalFieldCommand::class,  groups={"api"}))
     * @API\Parameter(name="duration", in="body", @Model(type=Command\CreateDurationFieldCommand::class, groups={"api"}))
     * @API\Parameter(name="issue",    in="body", @Model(type=Command\CreateIssueFieldCommand::class,    groups={"api"}))
     * @API\Parameter(name="list",     in="body", @Model(type=Command\CreateListFieldCommand::class,     groups={"api"}))
     * @API\Parameter(name="number",   in="body", @Model(type=Command\CreateNumberFieldCommand::class,   groups={"api"}))
     * @API\Parameter(name="string",   in="body", @Model(type=Command\CreateStringFieldCommand::class,   groups={"api"}))
     * @API\Parameter(name="text",     in="body", @Model(type=Command\CreateTextFieldCommand::class,     groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="State is not found.")
     * @API\Response(response=409, description="Field with specified name already exists.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function createField(Request $request, CommandBus $commandBus): JsonResponse
    {
        $class = FieldType::getCreateCommand($request->request->get('type'));

        if ($class === null) {
            return $this->json(null, JsonResponse::HTTP_BAD_REQUEST);
        }

        $command = new $class($request->request->all());

        /** @var Field $field */
        $field = $commandBus->handle($command);

        $url = $this->generateUrl('api_fields_get', [
            'id' => $field->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified field.
     *
     * @Route("/{id}", name="api_fields_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Field ID.")
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\TemplatesDomain\Model\API\Field::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Field is not found.")
     *
     * @param Field $field
     *
     * @return JsonResponse
     */
    public function getField(Field $field): JsonResponse
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->getDoctrine()->getManager();

        $main  = $field->jsonSerialize();
        $extra = $field->getFacade($manager)->jsonSerialize();

        return $this->json($main + $extra);
    }

    /**
     * Updates specified field.
     *
     * @Route("/{id}", name="api_fields_update", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id",       in="path", type="integer", required=true, description="Field ID.")
     * @API\Parameter(name="checkbox", in="body", @Model(type=Command\UpdateCheckboxFieldCommand::class, groups={"api"}))
     * @API\Parameter(name="date",     in="body", @Model(type=Command\UpdateDateFieldCommand::class,     groups={"api"}))
     * @API\Parameter(name="decimal",  in="body", @Model(type=Command\UpdateDecimalFieldCommand::class,  groups={"api"}))
     * @API\Parameter(name="duration", in="body", @Model(type=Command\UpdateDurationFieldCommand::class, groups={"api"}))
     * @API\Parameter(name="issue",    in="body", @Model(type=Command\UpdateIssueFieldCommand::class,    groups={"api"}))
     * @API\Parameter(name="list",     in="body", @Model(type=Command\UpdateListFieldCommand::class,     groups={"api"}))
     * @API\Parameter(name="number",   in="body", @Model(type=Command\UpdateNumberFieldCommand::class,   groups={"api"}))
     * @API\Parameter(name="string",   in="body", @Model(type=Command\UpdateStringFieldCommand::class,   groups={"api"}))
     * @API\Parameter(name="text",     in="body", @Model(type=Command\UpdateTextFieldCommand::class,     groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Field is not found.")
     * @API\Response(response=409, description="Field with specified name already exists.")
     *
     * @param Request    $request
     * @param Field      $field
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function updateField(Request $request, Field $field, CommandBus $commandBus): JsonResponse
    {
        $class = FieldType::getUpdateCommand($field->type);

        $command = new $class($request->request->all());

        $command->field = $field->id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified field.
     *
     * @Route("/{id}", name="api_fields_delete", methods={"DELETE"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Field ID.")
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
    public function deleteField(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\DeleteFieldCommand([
            'field' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns field's list items.
     *
     * @Route("/{id}/items", name="api_items_list", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Field ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\TemplatesDomain\Model\API\ListItem::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Field is not found.")
     *
     * @param Field              $field
     * @param ListItemRepository $repository
     *
     * @return JsonResponse
     */
    public function listItems(Field $field, ListItemRepository $repository): JsonResponse
    {
        $items = $repository->findAllByField($field);

        return $this->json($items);
    }

    /**
     * Creates new list item.
     *
     * @Route("/{id}/items", name="api_items_create", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Field ID.")
     * @API\Parameter(name="",   in="body", @Model(type=CreateListItemCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Field is not found.")
     * @API\Response(response=409, description="Item with specified value or text already exists.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function createItem(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new CreateListItemCommand($request->request->all());

        $command->field = $id;

        /** @var Field $field */
        $field = $commandBus->handle($command);

        $url = $this->generateUrl('api_items_get', [
            'id' => $field->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Sets position of the specified field.
     *
     * @Route("/{id}/position", name="api_fields_position", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Field ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\SetFieldPositionCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Field is not found.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function setFieldPosition(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\SetFieldPositionCommand($request->request->all());

        $command->field = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }
}
