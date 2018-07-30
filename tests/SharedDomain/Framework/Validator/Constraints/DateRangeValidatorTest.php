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

namespace eTraxis\SharedDomain\Framework\Validator\Constraints;

use eTraxis\Tests\WebTestCase;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class DateRangeValidatorTest extends WebTestCase
{
    /** @var \Symfony\Component\Validator\Validator\ValidatorInterface */
    protected $validator;

    protected function setUp()
    {
        parent::setUp();

        $this->validator = $this->client->getContainer()->get('validator');
    }

    public function testMissingOptions()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Either option "min" or "max" must be given for constraint "eTraxis\\SharedDomain\\Framework\\Validator\\Constraints\\DateRange".');

        $constraint = new DateRange();

        $this->validator->validate('2015-12-29', [$constraint]);
    }

    public function testInvalidMinOption()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "min" option given for constraint "eTraxis\\SharedDomain\\Framework\\Validator\\Constraints\\DateRange" is invalid.');

        $constraint = new DateRange([
            'min' => '2015-22-11',
        ]);

        $this->validator->validate('2015-12-29', [$constraint]);
    }

    public function testInvalidMaxOption()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "max" option given for constraint "eTraxis\\SharedDomain\\Framework\\Validator\\Constraints\\DateRange" is invalid.');

        $constraint = new DateRange([
            'max' => '2015-22-11',
        ]);

        $this->validator->validate('2015-12-29', [$constraint]);
    }

    public function testBothOptions()
    {
        $constraint = new DateRange([
            'min' => '2015-11-22',
            'max' => '2016-02-15',
        ]);

        $errors = $this->validator->validate('2015-11-21', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 2015-11-22 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2016-02-16', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 2016-02-15 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2015-11-22', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2016-02-15', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2015-22-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    public function testMinOptionOnly()
    {
        $constraint = new DateRange([
            'min' => '2015-11-22',
        ]);

        $errors = $this->validator->validate('2015-11-21', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 2015-11-22 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2015-11-22', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2016-02-16', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2015-22-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    public function testMaxOptionOnly()
    {
        $constraint = new DateRange([
            'max' => '2016-02-15',
        ]);

        $errors = $this->validator->validate('2016-02-16', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 2016-02-15 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2015-11-21', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2016-02-15', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2015-22-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    public function testCustomMinMessage()
    {
        $constraint = new DateRange([
            'min'        => '2015-11-22',
            'minMessage' => 'The value must be >= {{ limit }}.',
        ]);

        $errors = $this->validator->validate('2015-11-21', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('The value must be >= 2015-11-22.', $errors->get(0)->getMessage());
    }

    public function testCustomMaxMessage()
    {
        $constraint = new DateRange([
            'max'        => '2016-02-15',
            'maxMessage' => 'The value must be <= {{ limit }}.',
        ]);

        $errors = $this->validator->validate('2016-02-16', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('The value must be <= 2016-02-15.', $errors->get(0)->getMessage());
    }

    public function testCustomInvalidMessage()
    {
        $constraint = new DateRange([
            'min'            => '2015-11-22',
            'max'            => '2016-02-15',
            'invalidMessage' => 'The value is invalid.',
        ]);

        $errors = $this->validator->validate('2015-22-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('The value is invalid.', $errors->get(0)->getMessage());
    }
}
