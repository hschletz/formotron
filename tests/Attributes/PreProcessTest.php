<?php

namespace Formotron\Test\Attributes;

use Formotron\Attribute\PreProcess;
use Formotron\PreProcessor;
use Formotron\Test\DataProcessorTestTrait;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

class PreProcessTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testPreProcessAttributes()
    {
        $preProcessor1 = new class implements PreProcessor
        {
            public function process(array $formData): array
            {
                unset($formData['key1']);
                return $formData;
            }
        };
        $preProcessor2 = new class implements PreProcessor
        {
            public function process(array $formData): array
            {
                unset($formData['key2']);
                return $formData;
            }
        };
        $services = [
            ['PreProcessor1', $preProcessor1],
            ['PreProcessor2', $preProcessor2],
        ];


        $dataObject = new #[PreProcess('PreProcessor1')] #[PreProcess('PreProcessor2')] class
        {
        };
        $result = $this->process(['key1' => '', 'key2' => ''], $dataObject, $services);
        $this->assertInstanceOf(get_class($dataObject), $result);
        $this->assertObjectNotHasProperty('key1', $result);
        $this->assertObjectNotHasProperty('key2', $result);
    }

    public function testPreProcessAttributeWithInvalidService()
    {
        $preProcessor = new stdClass();
        $services = [['PreProcessor', $preProcessor]];

        $dataObject = new #[PreProcess('PreProcessor')] class
        {
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service PreProcessor does not implement ' . PreProcessor::class);
        $this->process(['key1' => ''], $dataObject, $services);
    }
}
