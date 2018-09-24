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

namespace eTraxis\IssuesDomain\Framework\Controller;

use eTraxis\IssuesDomain\Application\Voter\IssueVoter;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\IssuesDomain\Model\Repository\IssueRepository;
use eTraxis\IssuesDomain\Model\Repository\LastReadRepository;
use eTraxis\SharedDomain\Model\Collection\CollectionTrait;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API controller for '/issues' resource.
 *
 * @Route("/api/issues")
 * @Security("has_role('ROLE_USER')")
 *
 * @API\Tag(name="Issues")
 */
class ApiIssuesController extends Controller
{
    use CollectionTrait;

    /**
     * Returns list of issues.
     *
     * @Route("", name="api_issues_list", methods={"GET"})
     *
     * @API\Parameter(name="offset",   in="query", type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first issue to return.")
     * @API\Parameter(name="limit",    in="query", type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of issues to return.")
     * @API\Parameter(name="X-Search", in="body",  type="string",  required=false, description="Optional search value.", @API\Schema(type="string"))
     * @API\Parameter(name="X-Filter", in="body",  type="object",  required=false, description="Optional filters.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",               type="string"),
     *         @API\Property(property="subject",          type="string"),
     *         @API\Property(property="author",           type="integer"),
     *         @API\Property(property="author_name",      type="string"),
     *         @API\Property(property="project",          type="integer"),
     *         @API\Property(property="project_name",     type="string"),
     *         @API\Property(property="template",         type="integer"),
     *         @API\Property(property="template_name",    type="string"),
     *         @API\Property(property="state",            type="integer"),
     *         @API\Property(property="state_name",       type="string"),
     *         @API\Property(property="responsible",      type="integer"),
     *         @API\Property(property="responsible_name", type="string"),
     *         @API\Property(property="is_cloned",        type="boolean"),
     *         @API\Property(property="age",              type="integer"),
     *         @API\Property(property="is_critical",      type="boolean"),
     *         @API\Property(property="is_suspended",     type="boolean"),
     *         @API\Property(property="is_closed",        type="boolean")
     *     }
     * ))
     * @API\Parameter(name="X-Sort", in="body", type="object", required=false, description="Optional sorting.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",          type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="subject",     type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="created_at",  type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="changed_at",  type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="closed_at",   type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="author",      type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="project",     type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="template",    type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="state",       type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="responsible", type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="age",         type="string", enum={"ASC", "DESC"}, example="ASC")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned issue."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned issue."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found issues."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\IssuesDomain\Model\API\Issue::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param Request            $request
     * @param IssueRepository    $repository
     * @param LastReadRepository $lastReadRepository
     *
     * @return JsonResponse
     */
    public function listIssues(Request $request, IssueRepository $repository, LastReadRepository $lastReadRepository): JsonResponse
    {
        $collection = $this->getCollection($request, $repository);

        /** @var \eTraxis\IssuesDomain\Model\Entity\LastRead[] $lastReads */
        $lastReads = $lastReadRepository->findBy([
            'issue' => $collection->data,
            'user'  => $this->getUser(),
        ]);

        $values = [];

        foreach ($lastReads as $lastRead) {
            $values[$lastRead->issue->id] = $lastRead->readAt;
        }

        array_walk($collection->data, function (Issue &$issue) use ($values) {
            $readAt = $values[$issue->id] ?? null;
            $issue  = $issue->jsonSerialize();

            $issue[Issue::JSON_READ_AT] = $readAt;
        });

        return $this->json($collection);
    }

    /**
     * Returns specified issue.
     *
     * @Route("/{id}", name="api_issues_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\IssuesDomain\Model\API\Issue::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Issue              $issue
     * @param LastReadRepository $repository
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return JsonResponse
     */
    public function getIssue(Issue $issue, LastReadRepository $repository): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue);

        /** @var \eTraxis\IssuesDomain\Model\Entity\LastRead $lastRead */
        $lastRead = $repository->findOneBy([
            'issue' => $issue,
            'user'  => $this->getUser(),
        ]);

        $data = $issue->jsonSerialize();

        $data[Issue::JSON_READ_AT] = $lastRead === null ? null : $lastRead->readAt;

        $repository->markAsRead($issue, $this->getUser());

        return $this->json($data);
    }
}
