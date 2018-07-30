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

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Generic field interface.
 */
interface FieldInterface
{
    /**
     * Returns list of constraints for field value validation.
     *
     * @param TranslatorInterface $translator Translation service to configure error messages.
     *
     * @return \Symfony\Component\Validator\Constraint[]
     */
    public function getValidationConstraints(TranslatorInterface $translator): array;
}
