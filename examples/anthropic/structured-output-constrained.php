<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Demonstrates that JSON Schema constraints unsupported by Anthropic's API
// (minimum, maximum, minLength, maxItems, etc.) are moved to the `description`
// field by JsonSchemaSanitizerTrait so the model still reads and respects them.

use Symfony\AI\Platform\Bridge\Anthropic\Factory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\StructuredOutput\PlatformSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;

require_once dirname(__DIR__).'/bootstrap.php';

$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new PlatformSubscriber());

$platform = Factory::createPlatform(env('ANTHROPIC_API_KEY'), http_client(), eventDispatcher: $dispatcher);

$messages = new MessageBag(
    Message::forSystem('You are a product catalog assistant. Generate realistic product data that strictly respects all constraints in the schema descriptions.'),
    Message::ofUser('Create a product entry for a mid-range wireless headphone.'),
);

$result = $platform->invoke('claude-sonnet-4-5-20250929', $messages, ['response_format' => [
    'type' => 'json_schema',
    'json_schema' => [
        'name' => 'product',
        'strict' => true,
        'schema' => [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'The product name',
                    'minLength' => 5,
                    'maxLength' => 60,
                ],
                'price' => [
                    'type' => 'number',
                    'description' => 'Price in USD',
                    'minimum' => 1,
                    'maximum' => 500,
                    'multipleOf' => 0.01,
                ],
                'rating' => [
                    'type' => 'number',
                    'description' => 'Average customer rating',
                    'minimum' => 1.0,
                    'maximum' => 5.0,
                ],
                'tags' => [
                    'type' => 'array',
                    'description' => 'Descriptive tags for the product',
                    'items' => ['type' => 'string'],
                    'minItems' => 2,
                    'maxItems' => 6,
                ],
            ],
            'required' => ['name', 'price', 'rating', 'tags'],
            'additionalProperties' => false,
        ],
    ],
]]);

dump($result->getResult()->getContent());
