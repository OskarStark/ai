<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Tests\Contract\JsonSchema;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Fixtures\StructuredOutput\User;
use Symfony\AI\Fixtures\StructuredOutput\UserWithConstructor;
use Symfony\AI\Fixtures\Tool\ToolRequiredParams;
use Symfony\AI\Fixtures\Tool\ToolWithoutDocs;
use Symfony\AI\Platform\Contract\JsonSchema\DescriptionParser;

#[CoversClass(DescriptionParser::class)]
final class DescriptionParserTest extends TestCase
{
}
