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

namespace eTraxis\IssuesDomain\Model\Repository;

use eTraxis\IssuesDomain\Model\Entity\File;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\Tests\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\IssuesDomain\Model\Repository\FileRepository
 */
class FileRepositoryTest extends WebTestCase
{
    /** @var FileRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(File::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(FileRepository::class, $this->repository);
    }

    /**
     * @covers ::getFullPath
     */
    public function testFullPath()
    {
        /** @var File $file */
        [$file] = $this->repository->findAll();

        $expected = getcwd() . \DIRECTORY_SEPARATOR . 'var' . \DIRECTORY_SEPARATOR . $file->uuid;

        self::assertSame($expected, $this->repository->getFullPath($file));
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueWithRemoved()
    {
        $expected = [
            'Beatae nesciunt natus suscipit iure assumenda commodi.docx',
            'Possimus sapiente.pdf',
            'Nesciunt nulla sint amet.xslx',
        ];

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $files = array_map(function (File $file) {
            return $file->name;
        }, $this->repository->findAllByIssue($issue, true));

        self::assertSame($expected, $files);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueNoRemoved()
    {
        $expected = [
            'Beatae nesciunt natus suscipit iure assumenda commodi.docx',
            'Nesciunt nulla sint amet.xslx',
        ];

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $files = array_map(function (File $file) {
            return $file->name;
        }, $this->repository->findAllByIssue($issue));

        self::assertSame($expected, $files);
    }
}
