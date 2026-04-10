<?php

namespace Vendi\SesOffload\Vendor\Aws\Arn;

/**
 * @internal
 */
interface AccessPointArnInterface extends ArnInterface
{
    public function getAccesspointName();
}
