<?php

namespace Vendi\SesOffload\Vendor\Aws\Api\Parser;

use Vendi\SesOffload\Vendor\Aws\Api\Service;
use Vendi\SesOffload\Vendor\Aws\Api\StructureShape;
use Vendi\SesOffload\Vendor\Aws\CommandInterface;
use Vendi\SesOffload\Vendor\Aws\ResultInterface;
use Vendi\SesOffload\Vendor\GuzzleHttp\Psr7\CachingStream;
use Vendi\SesOffload\Vendor\Psr\Http\Message\ResponseInterface;
use Vendi\SesOffload\Vendor\Psr\Http\Message\StreamInterface;
/**
 * @internal
 */
abstract class AbstractParser
{
    /** @var \Aws\Api\Service Representation of the service API*/
    protected $api;
    /** @var callable */
    protected $parser;
    /**
     * @param Service $api Service description.
     */
    public function __construct(Service $api)
    {
        $this->api = $api;
    }
    /**
     * @param CommandInterface  $command  Command that was executed.
     * @param ResponseInterface $response Response that was received.
     *
     * @return ResultInterface
     */
    abstract public function __invoke(CommandInterface $command, ResponseInterface $response);
    abstract public function parseMemberFromStream(StreamInterface $stream, StructureShape $member, $response);
    public static function getBodyContents(ResponseInterface $response): string
    {
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        return $body->getContents();
    }
    public static function getResponseWithCachingStream(ResponseInterface $response): ResponseInterface
    {
        if (!$response->getBody()->isSeekable()) {
            return $response->withBody(new CachingStream($response->getBody()));
        }
        return $response;
    }
}
