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

namespace eTraxis\SharedDomain\Framework\Console;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \eTraxis\SharedDomain\Framework\Console\UuidCommand
 */
class UuidCommandTest extends WebTestCase
{
    /**
     * @covers ::configure
     * @covers ::execute
     */
    public function testUuid()
    {
        static::bootKernel();

        $application = new Application(self::$kernel);
        $application->add(new UuidCommand());

        $commandTester = new CommandTester($application->find('etraxis:uuid'));
        $commandTester->execute([]);

        self::assertRegExp('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}[\r\n]{1}$)/', $commandTester->getDisplay());
    }

    /**
     * @covers ::configure
     * @covers ::execute
     */
    public function testUuidHexOnly()
    {
        static::bootKernel();

        $application = new Application(self::$kernel);
        $application->add(new UuidCommand());

        $commandTester = new CommandTester($application->find('etraxis:uuid'));
        $commandTester->execute(['--hex-only' => true]);

        self::assertRegExp('/^([0-9a-f]{32}[\r\n]{1}$)/', $commandTester->getDisplay());
    }
}
