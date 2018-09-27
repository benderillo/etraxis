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

use eTraxis\IssuesDomain\Application\Command as Command;
use eTraxis\IssuesDomain\Application\Voter\IssueVoter;
use eTraxis\IssuesDomain\Model\Entity\File;
use eTraxis\IssuesDomain\Model\Repository\FileRepository;
use League\Tactician\CommandBus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API controller for '/files' resource.
 *
 * @Route("/api/files")
 * @Security("has_role('ROLE_USER')")
 *
 * @API\Tag(name="Files")
 */
class ApiFilesController extends Controller
{
    /**
     * Downloads specified file.
     *
     * @Route("/{id}", name="api_files_download", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="File ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="File is not found.")
     *
     * @param File           $file
     * @param FileRepository $repository
     *
     * @return BinaryFileResponse
     */
    public function downloadFile(File $file, FileRepository $repository): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $file->issue);

        if ($file->isRemoved) {
            throw $this->createNotFoundException();
        }

        $path = $repository->getFullPath($file);

        if (!file_exists($path)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($path);

        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $file->name);
        $response->setPrivate();

        return $response;
    }

    /**
     * Deletes specified file.
     *
     * @Route("/{id}", name="api_files_delete", methods={"DELETE"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="File ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="File is not found.")
     *
     * @param File       $file
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function deleteFile(File $file, CommandBus $commandBus): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $file->issue);

        $command = new Command\DeleteFileCommand([
            'file' => $file->id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }
}
