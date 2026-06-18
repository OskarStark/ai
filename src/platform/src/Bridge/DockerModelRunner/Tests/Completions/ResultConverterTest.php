<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\DockerModelRunner\Tests\Completions;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\DockerModelRunner\Completions;
use Symfony\AI\Platform\Bridge\DockerModelRunner\Completions\ResultConverter;
use Symfony\AI\Platform\Exception\ModelNotFoundException;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ResultConverterTest extends TestCase
{
    public function testItSupportsCompletionsModel()
    {
        $converter = new ResultConverter();

        $this->assertTrue($converter->supports(new Completions('test-model')));
    }

    #[TestWith(['Model not found'])]
    #[TestWith(['MODEL NOT FOUND'])]
    public function testItThrowsModelNotFoundExceptionWhen404WithModelNotFoundMessage(string $message)
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getStatusCode')
            ->willReturn(404);
        $response
            ->method('getContent')
            ->with(false)
            ->willReturn($message);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage($message);

        (new ResultConverter())->convert(new RawHttpResult($response));
    }

    public function testItDoesNotThrowModelNotFoundExceptionWhen404WithoutModelNotFoundMessage()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getStatusCode')
            ->willReturn(404);
        $response
            ->method('getContent')
            ->with(false)
            ->willReturn('Not found');
        $response
            ->method('toArray')
            ->willReturn(['error' => 'some other error']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Response does not contain choices.');

        (new ResultConverter())->convert(new RawHttpResult($response));
    }

    public function testThrowsOnUnhandledHttpErrorStatusBeforeStreaming()
    {
        $converter = new ResultConverter();
        $httpResponse = $this->createMock(ResponseInterface::class);
        $httpResponse->method('getStatusCode')->willReturn(500);
        $httpResponse->method('getContent')->willReturn('Service Unavailable');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unexpected response code 500');

        $converter->convert(new RawHttpResult($httpResponse), ['stream' => true]);
    }
}
