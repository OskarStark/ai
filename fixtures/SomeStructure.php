<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Fixtures;

final class SomeStructure
{
    public function __construct(?string $some = null)
    {
        if (null !== $some) {
            $this->some = $some;
        }
    }

    public string $some;
}
