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
use eTraxis\IssuesDomain\Model\Repository\LastReadRepository;
use eTraxis\SharedDomain\Model\Collection\CollectionTrait;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
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
