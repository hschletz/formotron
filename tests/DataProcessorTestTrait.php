<?php

namespace Formotron\Test;

use Formotron\DataProcessor;
use Psr\Container\ContainerInterface;

trait DataProcessorTestTrait
{
    /**
     * @template T of object
     * @param mixed[] $input
     * @param T $dataObject
     * @param list<list{string, object}> $services
     * @return T
     */
    private function process(array $input, object $dataObject, array $services = []): object
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnMap($services);

        $dataProcessor = new DataProcessor($container);
        $result = $dataProcessor->process($input, get_class($dataObject));

        return $result;
    }
}
