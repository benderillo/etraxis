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

namespace eTraxis\TemplatesDomain\Model\FieldTypes;

/**
 * Issue field trait.
 */
trait IssueTrait
{
    /**
     * Returns this field as a field of a "issue" type.
     *
     * @return IssueInterface
     */
    public function asIssue(): IssueInterface
    {
        return new class() implements IssueInterface {
        };
    }
}
