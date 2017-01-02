<?php
declare(strict_types = 1);


namespace T3G\Pagetemplates\Tests\Unit\Provider;


use T3G\Pagetemplates\Provider\TemplateProvider;

class TemplateProviderTest extends \PHPUnit_Framework_TestCase
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
