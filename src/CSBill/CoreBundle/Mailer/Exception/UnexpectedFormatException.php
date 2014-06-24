<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Mailer\Exception;

use UnexpectedValueException;

class UnexpectedFormatException extends UnexpectedValueException
{
    /**
     * @param string $format
     */
    public function __construct($format)
    {
        $message = sprintf('Invalid email format "%s" given', $format);
        parent::__construct($message, 0);
    }
}
