<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Perplexity;

use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\ModelCatalog\AbstractModelCatalog;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class ModelCatalog extends AbstractModelCatalog
{
    /**
     * @param array<string, array{class: string, capabilities: list<Capability>}> $additionalModels
     */
    public function __construct(array $additionalModels = [])
    {
        // STATIC LIST START
        // This list is generated from external metadata. Run dev/update-model-catalogs.php to refresh it.
        $defaultModels = [
            'sonar' => [
                'class' => Perplexity::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::INPUT_PDF,
                    Capability::OUTPUT_TEXT,
                    Capability::OUTPUT_STREAMING,
                    Capability::OUTPUT_STRUCTURED,
                    Capability::INPUT_IMAGE,
                ],
            ],
            'sonar-deep-research' => [
                'class' => Perplexity::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::INPUT_PDF,
                    Capability::OUTPUT_TEXT,
                    Capability::OUTPUT_STREAMING,
                    Capability::OUTPUT_STRUCTURED,
                ],
            ],
            'sonar-pro' => [
                'class' => Perplexity::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::INPUT_PDF,
                    Capability::OUTPUT_TEXT,
                    Capability::OUTPUT_STREAMING,
                    Capability::OUTPUT_STRUCTURED,
                    Capability::INPUT_IMAGE,
                ],
            ],
            'sonar-reasoning' => [
                'class' => Perplexity::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::INPUT_PDF,
                    Capability::OUTPUT_TEXT,
                    Capability::OUTPUT_STREAMING,
                    Capability::OUTPUT_STRUCTURED,
                    Capability::INPUT_IMAGE,
                ],
            ],
            'sonar-reasoning-pro' => [
                'class' => Perplexity::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::INPUT_PDF,
                    Capability::OUTPUT_TEXT,
                    Capability::OUTPUT_STREAMING,
                    Capability::OUTPUT_STRUCTURED,
                    Capability::INPUT_IMAGE,
                ],
            ],
        ];
        // STATIC LIST END

        $this->models = array_merge($defaultModels, $additionalModels);
    }
}
