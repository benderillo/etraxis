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

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use eTraxis\SharedDomain\Model\Dictionary\DatabasePlatform;

/**
 * Base migration.
 */
abstract class BaseMigration extends AbstractMigration
{
    /**
     * Returns version string for the migration.
     *
     * @return string
     */
    abstract public function getVersion(): string;

    /**
     * Checks whether the current database platform is MySQL.
     *
     * @return bool
     */
    public function isMysql(): bool
    {
        return DatabasePlatform::MYSQL === $this->platform->getName();
    }

    /**
     * Checks whether the current database platform is PostgreSQL.
     *
     * @return bool
     */
    public function isPostgresql(): bool
    {
        return DatabasePlatform::POSTGRESQL === $this->platform->getName();
    }

    /**
     * {@inheritdoc}
     */
    final public function getDescription()
    {
        return $this->getVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function preUp(Schema $schema)
    {
        $platform = $this->platform->getName();

        $this->abortIf(
            !DatabasePlatform::has($platform),
            'Unsupported database platform - ' . $platform
        );
    }

    /**
     * {@inheritdoc}
     */
    public function preDown(Schema $schema)
    {
        $platform = $this->platform->getName();

        $this->abortIf(
            !DatabasePlatform::has($platform),
            'Unsupported database platform - ' . $platform
        );
    }
}
