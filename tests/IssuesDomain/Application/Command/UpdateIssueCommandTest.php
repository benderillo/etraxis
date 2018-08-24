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

namespace eTraxis\IssuesDomain\Application\Command;

use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\Change;
use eTraxis\IssuesDomain\Model\Entity\FieldValue;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\TemplatesDomain\Model\Entity\DecimalValue;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\TemplatesDomain\Model\Entity\StringValue;
use eTraxis\TemplatesDomain\Model\Entity\TextValue;
use eTraxis\Tests\TransactionalTestCase;
use League\Tactician\Bundle\Middleware\InvalidCommandException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateIssueCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\IssuesDomain\Model\Repository\IssueRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    public function testSuccess()
    {
        $index = [
            'Commit ID'     => 0,
            'Delta'         => 1,
            'Description'   => 2,
            'Due date'      => 3,
            'Effort'        => 4,
            'Error'         => 5,
            'Priority'      => 6,
            'Test coverage' => 7,
        ];

        /** @var \eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository $decimalRepository */
        $decimalRepository = $this->doctrine->getRepository(DecimalValue::class);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\StringValueRepository $stringRepository */
        $stringRepository = $this->doctrine->getRepository(StringValue::class);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TextValueRepository $textRepository */
        $textRepository = $this->doctrine->getRepository(TextValue::class);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository $listRepository */
        $listRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $values = $issue->values;

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return strcmp($value1->field->name, $value2->field->name);
        });

        self::assertGreaterThan(1, time() - $issue->changedAt);
        self::assertSame('Development task 1', $issue->subject);
        self::assertSame('normal', $listRepository->find($values[$index['Priority']]->value)->text);
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->value)->value);
        self::assertSame(0, $values[$index['Error']]->value);
        self::assertNull($values[$index['Due date']]->value);
        self::assertNull($values[$index['Commit ID']]->value);
        self::assertSame(5173, $values[$index['Delta']]->value);
        self::assertSame(1440, $values[$index['Effort']]->value);
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->value)->value);

        $events  = count($issue->events);
        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $values[$index['Priority']]->field->id      => 1,
                $values[$index['Description']]->field->id   => 'Est dolorum omnis accusantium hic veritatis ut.',
                $values[$index['Error']]->field->id         => true,
                $values[$index['Due date']]->field->id      => '2017-04-22',
                $values[$index['Commit ID']]->field->id     => 'fb6c40d246aeeb8934884febcd18d19555fd7725',
                $values[$index['Delta']]->field->id         => 5182,
                $values[$index['Effort']]->field->id        => '7:40',
                $values[$index['Test coverage']]->field->id => '98.52',
            ],
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $issue->values;

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return strcmp($value1->field->name, $value2->field->name);
        });

        $date = date_create();
        $date->setTimezone(timezone_open($user->timezone));

        self::assertLessThanOrEqual(1, time() - $issue->changedAt);
        self::assertSame('Test issue', $issue->subject);
        self::assertSame('high', $listRepository->find($values[$index['Priority']]->value)->text);
        self::assertSame('Est dolorum omnis accusantium hic veritatis ut.', $textRepository->find($values[$index['Description']]->value)->value);
        self::assertSame(1, $values[$index['Error']]->value);
        self::assertSame('2017-04-22', $date->setTimestamp($values[$index['Due date']]->value)->format('Y-m-d'));
        self::assertSame('fb6c40d246aeeb8934884febcd18d19555fd7725', $stringRepository->find($values[$index['Commit ID']]->value)->value);
        self::assertSame(5182, $values[$index['Delta']]->value);
        self::assertSame(460, $values[$index['Effort']]->value);
        self::assertSame('98.52', $decimalRepository->find($values[$index['Test coverage']]->value)->value);

        self::assertCount($events + 1, $issue->events);
        self::assertCount($changes + 9, $this->doctrine->getRepository(Change::class)->findAll());

        $events = $issue->events;
        $event  = end($events);

        self::assertSame(EventType::ISSUE_EDITED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertSame($user, $event->user);
        self::assertLessThanOrEqual(1, time() - $event->createdAt);
        self::assertNull($event->parameter);
    }

    public function testSuccessOnlySubject()
    {
        $index = [
            'Commit ID'     => 0,
            'Delta'         => 1,
            'Description'   => 2,
            'Due date'      => 3,
            'Effort'        => 4,
            'Error'         => 5,
            'Priority'      => 6,
            'Test coverage' => 7,
        ];

        /** @var \eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository $decimalRepository */
        $decimalRepository = $this->doctrine->getRepository(DecimalValue::class);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TextValueRepository $textRepository */
        $textRepository = $this->doctrine->getRepository(TextValue::class);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository $listRepository */
        $listRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $values = $issue->values;

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return strcmp($value1->field->name, $value2->field->name);
        });

        self::assertGreaterThan(1, time() - $issue->changedAt);
        self::assertSame('Development task 1', $issue->subject);
        self::assertSame('normal', $listRepository->find($values[$index['Priority']]->value)->text);
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->value)->value);
        self::assertSame(0, $values[$index['Error']]->value);
        self::assertNull($values[$index['Due date']]->value);
        self::assertNull($values[$index['Commit ID']]->value);
        self::assertSame(5173, $values[$index['Delta']]->value);
        self::assertSame(1440, $values[$index['Effort']]->value);
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->value)->value);

        $events  = count($issue->events);
        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $issue->values;

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return strcmp($value1->field->name, $value2->field->name);
        });

        self::assertLessThanOrEqual(1, time() - $issue->changedAt);
        self::assertSame('Test issue', $issue->subject);
        self::assertSame('normal', $listRepository->find($values[$index['Priority']]->value)->text);
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->value)->value);
        self::assertSame(0, $values[$index['Error']]->value);
        self::assertNull($values[$index['Due date']]->value);
        self::assertNull($values[$index['Commit ID']]->value);
        self::assertSame(5173, $values[$index['Delta']]->value);
        self::assertSame(1440, $values[$index['Effort']]->value);
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->value)->value);

        self::assertCount($events + 1, $issue->events);
        self::assertCount($changes + 1, $this->doctrine->getRepository(Change::class)->findAll());

        $events = $issue->events;
        $event  = end($events);

        self::assertSame(EventType::ISSUE_EDITED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertSame($user, $event->user);
        self::assertLessThanOrEqual(1, time() - $event->createdAt);
        self::assertNull($event->parameter);
    }

    public function testSuccessOnlyRequiredFields()
    {
        $index = [
            'Commit ID'     => 0,
            'Delta'         => 1,
            'Description'   => 2,
            'Due date'      => 3,
            'Effort'        => 4,
            'Error'         => 5,
            'Priority'      => 6,
            'Test coverage' => 7,
        ];

        /** @var \eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository $decimalRepository */
        $decimalRepository = $this->doctrine->getRepository(DecimalValue::class);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TextValueRepository $textRepository */
        $textRepository = $this->doctrine->getRepository(TextValue::class);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository $listRepository */
        $listRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $values = $issue->values;

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return strcmp($value1->field->name, $value2->field->name);
        });

        self::assertGreaterThan(1, time() - $issue->changedAt);
        self::assertSame('Development task 1', $issue->subject);
        self::assertSame('normal', $listRepository->find($values[$index['Priority']]->value)->text);
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->value)->value);
        self::assertSame(0, $values[$index['Error']]->value);
        self::assertNull($values[$index['Due date']]->value);
        self::assertNull($values[$index['Commit ID']]->value);
        self::assertSame(5173, $values[$index['Delta']]->value);
        self::assertSame(1440, $values[$index['Effort']]->value);
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->value)->value);

        $events  = count($issue->events);
        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => null,
            'fields'  => [
                $values[$index['Priority']]->field->id => 1,
                $values[$index['Delta']]->field->id    => 5182,
                $values[$index['Effort']]->field->id   => '7:40',
            ],
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $issue->values;

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return strcmp($value1->field->name, $value2->field->name);
        });

        self::assertLessThanOrEqual(1, time() - $issue->changedAt);
        self::assertSame('Development task 1', $issue->subject);
        self::assertSame('high', $listRepository->find($values[$index['Priority']]->value)->text);
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->value)->value);
        self::assertSame(0, $values[$index['Error']]->value);
        self::assertNull($values[$index['Due date']]->value);
        self::assertNull($values[$index['Commit ID']]->value);
        self::assertSame(5182, $values[$index['Delta']]->value);
        self::assertSame(460, $values[$index['Effort']]->value);
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->value)->value);

        self::assertCount($events + 1, $issue->events);
        self::assertCount($changes + 3, $this->doctrine->getRepository(Change::class)->findAll());

        $events = $issue->events;
        $event  = end($events);

        self::assertSame(EventType::ISSUE_EDITED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertSame($user, $event->user);
        self::assertLessThanOrEqual(1, time() - $event->createdAt);
        self::assertNull($event->parameter);
    }

    public function testValidationRequiredFields()
    {
        $this->expectException(InvalidCommandException::class);
        $this->expectExceptionMessage('Validation failed for eTraxis\IssuesDomain\Application\Command\UpdateIssueCommand with 3 violation(s).');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = $issue->values;

        usort($values, function (FieldValue $value1, FieldValue $value2) {
            return strcmp($value1->field->name, $value2->field->name);
        });

        $command = new UpdateIssueCommand([
            'issue'  => $issue->id,
            'fields' => [
                $values[0]->field->id => null,
                $values[1]->field->id => null,
                $values[2]->field->id => null,
                $values[3]->field->id => null,
                $values[4]->field->id => null,
                $values[5]->field->id => null,
                $values[6]->field->id => null,
                $values[7]->field->id => null,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testValidationOnListField()
    {
        $this->expectException(InvalidCommandException::class);
        $this->expectExceptionMessage('Validation failed for eTraxis\IssuesDomain\Application\Command\UpdateIssueCommand with 1 violation(s).');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => 4,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testValidationOnTextField()
    {
        $this->expectException(InvalidCommandException::class);
        $this->expectExceptionMessage('Validation failed for eTraxis\IssuesDomain\Application\Command\UpdateIssueCommand with 1 violation(s).');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Description']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => str_pad(null, 4001, '*'),
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testValidationOnCheckboxField()
    {
        $this->expectException(InvalidCommandException::class);
        $this->expectExceptionMessage('Validation failed for eTraxis\IssuesDomain\Application\Command\UpdateIssueCommand with 1 violation(s).');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Error']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => 0,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testValidationOnDateField()
    {
        $this->expectException(InvalidCommandException::class);
        $this->expectExceptionMessage('Validation failed for eTraxis\IssuesDomain\Application\Command\UpdateIssueCommand with 1 violation(s).');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Due date']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => '2004-07-08',
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testValidationOnStringField()
    {
        $this->expectException(InvalidCommandException::class);
        $this->expectExceptionMessage('Validation failed for eTraxis\IssuesDomain\Application\Command\UpdateIssueCommand with 1 violation(s).');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Commit ID']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => str_pad(null, 41, '*'),
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testValidationOnNumberField()
    {
        $this->expectException(InvalidCommandException::class);
        $this->expectExceptionMessage('Validation failed for eTraxis\IssuesDomain\Application\Command\UpdateIssueCommand with 1 violation(s).');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Delta']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => -1,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testValidationOnDurationField()
    {
        $this->expectException(InvalidCommandException::class);
        $this->expectExceptionMessage('Validation failed for eTraxis\IssuesDomain\Application\Command\UpdateIssueCommand with 2 violation(s).');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Effort']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => '1000000:00',
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testValidationOnDecimalField()
    {
        $this->expectException(InvalidCommandException::class);
        $this->expectExceptionMessage('Validation failed for eTraxis\IssuesDomain\Application\Command\UpdateIssueCommand with 1 violation(s).');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Test coverage']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => '100.01',
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownIssue()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginAs('ldoyle@example.com');

        $command = new UpdateIssueCommand([
            'issue'   => self::UNKNOWN_ENTITY_ID,
            'subject' => 'Test issue',
        ]);

        $this->commandbus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to edit this issue.');

        $this->loginAs('labshire@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
        ]);

        $this->commandbus->handle($command);
    }

    public function testSuspendedProject()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
        ]);

        $this->commandbus->handle($command);
    }

    public function testLockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
        ]);

        $this->commandbus->handle($command);
    }

    public function testSuspendedIssue()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
        ]);

        $this->commandbus->handle($command);
    }

    public function testFrozenIssue()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $issue->template->frozenTime = 1;

        $command = new UpdateIssueCommand([
            'issue'   => $issue->id,
            'subject' => 'Test issue',
        ]);

        $this->commandbus->handle($command);
    }
}
