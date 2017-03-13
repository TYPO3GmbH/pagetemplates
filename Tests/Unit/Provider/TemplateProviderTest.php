<?php
declare(strict_types=1);

namespace T3G\AgencyPack\Pagetemplates\Tests\Unit\Provider;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use PHPUnit\Framework\TestCase;
use T3G\AgencyPack\Pagetemplates\Provider\TemplateProvider;

class TemplateProviderTest extends TestCase
{

    /**
     * @test
     * @return void
     */
    public function getTemplatesReturnsTemplateArray()
    {
        $examplePath = __DIR__ . '/../../../Configuration/Templates';
        $templateProvider = new TemplateProvider($examplePath);
        $templates = $templateProvider->getTemplates();
        $expected = [
            'example1' =>
                [
                    'name' => 'Example Template 1',
                    'previewImage' => 'EXT:pagetemplates/ext_icon.svg',
                    'description' => 'This is an example template for use in the templates extension.',
                ],
            'example2' =>
                [
                    'name' => 'Example Template 2',
                    'previewImage' => 'EXT:pagetemplates/ext_icon.svg',
                    'description' => 'This is an example template for use in the templates extension.',
                ],
        ];
        self::assertSame($expected, $templates);
    }

}
