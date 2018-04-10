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

namespace eTraxis\TemplatesDomain\Model\Repository;

use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\WebTestCase;

class TemplateRepositoryTest extends WebTestCase
{
    public function testRepository()
    {
        $repository = $this->doctrine->getRepository(Template::class);

        self::assertInstanceOf(TemplateRepository::class, $repository);
    }
}
