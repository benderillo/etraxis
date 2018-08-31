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

namespace eTraxis\IssuesDomain\Application\Command;

use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\File;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeleteFileCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\IssuesDomain\Model\Repository\FileRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(File::class);
    }

    public function testSuccess()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->repository->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        self::assertNotNull($file);
        self::assertFalse($file->isRemoved);

        $filename = 'var' . \DIRECTORY_SEPARATOR . $file->uuid;
        file_put_contents($filename, str_repeat('*', $file->size));
        self::assertFileExists($filename);

        $events = count($file->issue->events);
        $files  = count($this->repository->findAll());

        $command = new DeleteFileCommand([
            'file' => $file->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($file->issue);

        self::assertCount($events + 1, $file->issue->events);
        self::assertCount($files, $this->repository->findAll());

        $events = $file->issue->events;
        $event  = end($events);

        self::assertSame(EventType::FILE_DELETED, $event->type);
        self::assertSame($file->issue, $event->issue);
        self::assertSame($user, $event->user);
        self::assertLessThanOrEqual(2, time() - $event->createdAt);
        self::assertSame($file->id, $event->parameter);

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->repository->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        self::assertNotNull($file);
        self::assertTrue($file->isRemoved);
        self::assertFileNotExists($filename);
    }

    public function testUnknownFile()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown file.');

        $this->loginAs('ldoyle@example.com');

        $command = new DeleteFileCommand([
            'file' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandbus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this file.');

        $this->loginAs('fdooley@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->repository->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $command = new DeleteFileCommand([
            'file' => $file->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testSuspendedProject()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [$file] = $this->repository->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $command = new DeleteFileCommand([
            'file' => $file->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testLockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, $file] = $this->repository->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $command = new DeleteFileCommand([
            'file' => $file->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testSuspendedIssue()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->repository->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $file->issue->suspend(time() + 86400);

        $command = new DeleteFileCommand([
            'file' => $file->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testFrozenIssue()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->repository->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $file->issue->template->frozenTime = 1;

        $command = new DeleteFileCommand([
            'file' => $file->id,
        ]);

        $this->commandbus->handle($command);
    }
}
