<?php

namespace Pim\Component\Api\Hal;

/**
 * Interface to manipulate a link with the HAL format.
 *
 * @author    Alexandre Hocquard <alexandre.hocquard@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface LinkInterface
{
    /**
     * Generate the link into an array with the HAL format.
     *
     * @return array
     */
    public function toArray();
}
