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
use eTraxis\Tests\WebTestCase;

class FileRepositoryTest extends WebTestCase
{
    /** @var FileRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(File::class);
    }

    public function testRepository()
    {
        self::assertInstanceOf(FileRepository::class, $this->repository);
    }

    public function testFullPath()
    {
        /** @var File $file */
        [$file] = $this->repository->findAll();

        $expected = getcwd() . \DIRECTORY_SEPARATOR . 'var' . \DIRECTORY_SEPARATOR . $file->uuid;

        self::assertSame($expected, $this->repository->getFullPath($file));
    }
}
