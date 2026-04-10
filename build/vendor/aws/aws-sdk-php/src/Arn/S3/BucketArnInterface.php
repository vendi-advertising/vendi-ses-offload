<?php

namespace Vendi\SesOffload\Vendor\Aws\Arn\S3;

use Vendi\SesOffload\Vendor\Aws\Arn\ArnInterface;
/**
 * @internal
 */
interface BucketArnInterface extends ArnInterface
{
    public function getBucketName();
}
