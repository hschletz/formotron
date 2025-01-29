<?php

namespace Formotron\Test\Attributes;

use Formotron\AssertionFailedException;
use Formotron\Attribute\Assert;
use Formotron\Test\DataProcessorTestTrait;
use Formotron\Validator;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

class AssertTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testAssertionsPass()
    {
        $validator1 = $this->createMock(Validator::class);
        $validator1->expects($this->once())->method('getValidationErrors')->with('bar')->willReturn([]);

        $validator2 = $this->createMock(Validator::class);
        $validator2->expects($this->once())->method('getValidationErrors')->with('bar')->willReturn([]);

        $services = [
            ['Validator1', $validator1],
            ['Validator2', $validator2],
        ];

        $dataObject = new class
        {
            #[Assert('Validator1')]
            #[Assert('Validator2')]
            public mixed $foo;
        };
        $result = $this->process(['foo' => 'bar'], $dataObject, $services);
        $this->assertEquals('bar', $result->foo);
    }

    public function testAssertionFails()
    {
        $validator = $this->createMock(Validator::class);
        $validator->method('getValidationErrors')->willReturn(['error1', 'error2']);
        $services = [['Validator', $validator]];

        $dataObject = new class
        {
            #[Assert('Validator')]
            public mixed $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Assertion Validator failed on $foo');
        $this->process(['foo' => 'bar'], $dataObject, $services);
    }

    public function testInvalidService()
    {
        $validator = new stdClass();
        $services = [['Validator', $validator]];

        $dataObject = new class
        {
            #[Assert('Validator')]
            public mixed $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service Validator does not implement ' . Validator::class);
        $this->process(['foo' => 'bar'], $dataObject, $services);
    }
}
