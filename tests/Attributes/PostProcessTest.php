<?php

namespace Formotron\Test\Attributes;

use Formotron\Attribute\PostProcess;
use Formotron\PostProcessor;
use Formotron\Test\DataProcessorTestTrait;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

class PostProcessTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testPostProcessAttributes()
    {
        $dataObject = new #[PostProcess('PostProcessor1')] #[PostProcess('PostProcessor2')] class {
            public string $key1;
            public string $key2;
        };

        $postProcessor1 = new class(get_class($dataObject)) implements PostProcessor
        {
            /** @param class-string $class */
            public function __construct(private string $class) {}

            public function process(object $dataObject): void
            {
                TestCase::assertInstanceOf($this->class, $dataObject);
                $dataObject->key1 = 'value1'; // @phpstan-ignore property.notFound (cannot handle anonymous class)
            }
        };
        $postProcessor2 = new class(get_class($dataObject)) implements PostProcessor
        {
            /** @param class-string $class */
            public function __construct(private string $class) {}

            public function process(object $dataObject): void
            {
                TestCase::assertInstanceOf($this->class, $dataObject);
                $dataObject->key2 = 'value2'; // @phpstan-ignore property.notFound (cannot handle anonymous class)
            }
        };
        $services = [
            ['PostProcessor1', $postProcessor1],
            ['PostProcessor2', $postProcessor2],
        ];

        $result = $this->process(['key1' => '', 'key2' => ''], $dataObject, $services);
        $this->assertInstanceOf(get_class($dataObject), $result);
        $this->assertEquals('value1', $result->key1);
        $this->assertEquals('value2', $result->key2);
    }

    public function testPostProcessAttributeWithInvalidService()
    {
        $postProcessor = new stdClass();
        $services = [['PostProcessor', $postProcessor]];

        $dataObject = new #[PostProcess('PostProcessor')] class {};
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service PostProcessor does not implement ' . PostProcessor::class);
        $this->process([], $dataObject, $services);
    }
}
