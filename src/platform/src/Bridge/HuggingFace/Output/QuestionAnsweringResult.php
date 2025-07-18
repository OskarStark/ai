<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\HuggingFace\Output;

/**
 * @author Christopher Hertel <mail@christopher-hertel.de>
 */
final readonly class QuestionAnsweringResult
{
    public function __construct(
        public string $answer,
        public int $startIndex,
        public int $endIndex,
        public float $score,
    ) {
    }

    /**
     * @param array{answer: string, start: int, end: int, score: float} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['answer'],
            $data['start'],
            $data['end'],
            $data['score'],
        );
    }
}
