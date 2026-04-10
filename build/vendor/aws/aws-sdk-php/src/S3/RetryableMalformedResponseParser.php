<?php

namespace Vendi\SesOffload\Vendor\Aws\S3;

use Vendi\SesOffload\Vendor\Aws\Api\Parser\AbstractParser;
use Vendi\SesOffload\Vendor\Aws\Api\StructureShape;
use Vendi\SesOffload\Vendor\Aws\Api\Parser\Exception\ParserException;
use Vendi\SesOffload\Vendor\Aws\CommandInterface;
use Vendi\SesOffload\Vendor\Aws\Exception\AwsException;
use Vendi\SesOffload\Vendor\Psr\Http\Message\ResponseInterface;
use Vendi\SesOffload\Vendor\Psr\Http\Message\StreamInterface;
/**
 * Converts malformed responses to a retryable error type.
 *
 * @internal
 */
class RetryableMalformedResponseParser extends AbstractParser
{
    /** @var string */
    private $exceptionClass;
    public function __construct(callable $parser, $exceptionClass = AwsException::class)
    {
        $this->parser = $parser;
        $this->exceptionClass = $exceptionClass;
    }
    public function __invoke(CommandInterface $command, ResponseInterface $response)
    {
        $fn = $this->parser;
        try {
            return $fn($command, $response);
        } catch (ParserException $e) {
            throw new $this->exceptionClass("Error parsing response for {$command->getName()}:" . " AWS parsing error: {$e->getMessage()}", $command, ['connection_error' => \true, 'exception' => $e], $e);
        }
    }
    public function parseMemberFromStream(StreamInterface $stream, StructureShape $member, $response)
    {
        return $this->parser->parseMemberFromStream($stream, $member, $response);
    }
}
