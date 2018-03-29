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

use Doctrine\DBAL\Schema\Schema;

class DummyMigration extends BaseMigration
{
    public function getVersion(): string
    {
        return '4.0.0';
    }

    public function up(Schema $schema)
    {
        echo 'migrating up';
    }

    public function down(Schema $schema)
    {
        echo 'migrating down';
    }
}
