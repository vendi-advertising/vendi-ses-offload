<?php

namespace Vendi\SesOffload\Vendor\Aws\Arn\S3;

use Vendi\SesOffload\Vendor\Aws\Arn\ArnInterface;
/**
 * @internal
 */
interface OutpostsArnInterface extends ArnInterface
{
    public function getOutpostId();
}
