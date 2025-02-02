<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "sf_event_mgt" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace DERHANSEN\SfEventMgt\Tests\Unit\Service;

use DERHANSEN\SfEventMgt\Domain\Model\Event;
use DERHANSEN\SfEventMgt\Domain\Model\Registration;
use DERHANSEN\SfEventMgt\Domain\Repository\EventRepository;
use DERHANSEN\SfEventMgt\Domain\Repository\RegistrationRepository;
use DERHANSEN\SfEventMgt\Service\ExportService;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExportServiceTest extends UnitTestCase
{
    public static function fieldValuesInTypoScriptDataProvider(): array
    {
        return [
            'fieldValuesWithWhitespacesInTypoScript' => [
                1,
                [
                    'fields' => 'uid, firstname, lastname',
                    'fieldDelimiter' => ',',
                    'fieldQuoteCharacter' => '"',
                    'prependBOM' => 0,
                ],
                '"uid","firstname","lastname"' . chr(10) . '1,"Max","Mustermann"' . chr(10),
            ],
            'fieldValuesWithoutWhitespacesInTypoScript' => [
                1,
                [
                    'fields' => 'uid, firstname, lastname',
                    'fieldDelimiter' => ',',
                    'fieldQuoteCharacter' => '"',
                    'prependBOM' => 0,
                ],
                '"uid","firstname","lastname"' . chr(10) . '1,"Max","Mustermann"' . chr(10),
            ],
            'fieldValuesWithDifferentDelimiter' => [
                1,
                [
                    'fields' => 'uid, firstname, lastname',
                    'fieldDelimiter' => ';',
                    'fieldQuoteCharacter' => '"',
                    'prependBOM' => 0,
                ],
                '"uid";"firstname";"lastname"' . chr(10) . '1;"Max";"Mustermann"' . chr(10),
            ],
            'fieldValuesWithDifferentQuoteCharacter' => [
                1,
                [
                    'fields' => 'uid, firstname, lastname',
                    'fieldDelimiter' => ',',
                    'fieldQuoteCharacter' => '\'',
                    'prependBOM' => 0,
                ],
                '\'uid\',\'firstname\',\'lastname\'' . chr(10) . '1,\'Max\',\'Mustermann\'' . chr(10),
            ],
            'fieldValuesWithBomSetting' => [
                1,
                [
                    'fields' => 'uid, firstname, lastname',
                    'fieldDelimiter' => ';',
                    'fieldQuoteCharacter' => '"',
                    'prependBOM' => 1,
                ],
                chr(239) . chr(187) . chr(191) . '"uid";"firstname";"lastname"' . chr(10) . '1;"Max";"Mustermann"' . chr(10),
            ],
            'fieldValuesWithSubproperty' => [
                1,
                [
                    'fields' => 'uid,firstname,lastname,event.title',
                    'fieldDelimiter' => ',',
                    'fieldQuoteCharacter' => '"',
                    'prependBOM' => 0,
                ],
                '"uid","firstname","lastname","event.title"' . chr(10) . '1,"Max","Mustermann","Some event"' . chr(10),
            ],
            'fieldValuesWithNonExistingFieldReturnsEmptyString' => [
                1,
                [
                    'fields' => 'uid,firstname,lastname,foo',
                    'fieldDelimiter' => ',',
                    'fieldQuoteCharacter' => '"',
                    'prependBOM' => 0,
                ],
                '"uid","firstname","lastname","foo"' . chr(10) . '1,"Max","Mustermann",' . chr(10),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider fieldValuesInTypoScriptDataProvider
     */
    public function exportServiceWorksWithDifferentFormattedTypoScriptValues(
        int $uid,
        array $fields,
        string $expected
    ): void {
        $event = new Event();
        $event->setTitle('Some event');

        $registration = new Registration();
        $registration->setFirstname('Max');
        $registration->setLastname('Mustermann');
        $registration->_setProperty('uid', 1);
        $registration->setEvent($event);

        $allRegistrations = new ObjectStorage();
        $allRegistrations->attach($registration);

        $registrationRepository = $this->getMockBuilder(RegistrationRepository::class)
            ->addMethods(['findByEvent'])
            ->disableOriginalConstructor()
            ->getMock();
        $registrationRepository->expects(self::once())->method('findByEvent')->willReturn(
            $allRegistrations
        );

        $eventRepository = $this->getMockBuilder(EventRepository::class)->disableOriginalConstructor()->getMock();
        $exportService = new ExportService($registrationRepository, $eventRepository);

        $returnValue = $exportService->exportRegistrationsCsv($uid, $fields);
        self::assertSame($expected, $returnValue);
    }
}
