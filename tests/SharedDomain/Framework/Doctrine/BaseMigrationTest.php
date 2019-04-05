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

namespace eTraxis\SharedDomain\Framework\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\AbortMigration;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;

class BaseMigrationTest extends TestCase
{
    public function testVersion()
    {
        $expected = '4.0.0';

        $version   = $this->getVersion(MySqlPlatform::class);
        $migration = $this->getMigration($version);

        self::assertSame($expected, $migration->getVersion());
        self::assertSame($expected, $migration->getDescription());
    }

    public function testIsMysql()
    {
        $version   = $this->getVersion(MySqlPlatform::class);
        $migration = $this->getMigration($version);

        self::assertTrue($migration->isMysql());
        self::assertFalse($migration->isPostgresql());
    }

    public function testIsPostgresql()
    {
        $version   = $this->getVersion(PostgreSqlPlatform::class);
        $migration = $this->getMigration($version);

        self::assertTrue($migration->isPostgresql());
        self::assertFalse($migration->isMysql());
    }

    public function testUpSuccess()
    {
        $schema    = new Schema();
        $version   = $this->getVersion(MySqlPlatform::class);
        $migration = $this->getMigration($version);

        $this->expectOutputString('migrating up');
        $migration->preUp($schema);
        $migration->up($schema);
    }

    public function testDownSuccess()
    {
        $schema    = new Schema();
        $version   = $this->getVersion(MySqlPlatform::class);
        $migration = $this->getMigration($version);

        $this->expectOutputString('migrating down');
        $migration->preDown($schema);
        $migration->down($schema);
    }

    public function testUpFailure()
    {
        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage('Unsupported database platform - sqlite');

        $schema    = new Schema();
        $version   = $this->getVersion(SqlitePlatform::class);
        $migration = $this->getMigration($version);

        $migration->preUp($schema);
        $migration->up($schema);
    }

    public function testDownFailure()
    {
        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage('Unsupported database platform - sqlite');

        $schema    = new Schema();
        $version   = $this->getVersion(SqlitePlatform::class);
        $migration = $this->getMigration($version);

        $migration->preDown($schema);
        $migration->down($schema);
    }

    protected function getVersion(string $class)
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getSchemaManager')
            ->willReturn(null);
        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new $class());

        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('getConnection')
            ->willReturn($connection);

        $version = $this->createMock(Version::class);
        $version
            ->method('getConfiguration')
            ->willReturn($configuration);

        /** @var Version $version */
        return $version;
    }

    protected function getMigration(Version $version)
    {
        return new class($version) extends BaseMigration {
            public function getVersion(): string
            {
                return '4.0.0';
            }

            public function up(Schema $schema): void
            {
                echo 'migrating up';
            }

            public function down(Schema $schema): void
            {
                echo 'migrating down';
            }
        };
    }
}
