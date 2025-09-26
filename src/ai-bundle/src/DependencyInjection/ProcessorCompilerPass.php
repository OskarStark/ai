<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\AiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProcessorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $inputProcessors = $container->findTaggedServiceIds('ai.agent.input_processor');
        $outputProcessors = $container->findTaggedServiceIds('ai.agent.output_processor');
        $agentsWithToolsDisabled = array_keys($container->findTaggedServiceIds('ai.agent.tools_disabled'));

        foreach ($container->findTaggedServiceIds('ai.agent') as $serviceId => $tags) {
            $agentInputProcessors = [];
            $agentOutputProcessors = [];
            
            // Check if this agent has tools disabled
            $toolsDisabled = in_array($serviceId, $agentsWithToolsDisabled, true);
            
            foreach ($inputProcessors as $processorId => $processorTags) {
                foreach ($processorTags as $tag) {
                    if ('interface' === ($tag['tagged_by'] ?? null) && \count($processorTags) > 1) {
                        continue;
                    }

                    $agent = $tag['agent'] ?? null;
                    // Skip global processors (agent === null) if tools are disabled for this agent
                    if (null === $agent && $toolsDisabled) {
                        // Check if this is a tool-related processor by examining the service ID
                        if (str_contains($processorId, 'tool')) {
                            continue;
                        }
                    }
                    
                    if (null === $agent || $agent === $serviceId) {
                        $priority = $tag['priority'] ?? 0;
                        $agentInputProcessors[] = [$priority, new Reference($processorId)];
                    }
                }
            }

            foreach ($outputProcessors as $processorId => $processorTags) {
                foreach ($processorTags as $tag) {
                    if ('interface' === ($tag['tagged_by'] ?? null) && \count($processorTags) > 1) {
                        continue;
                    }

                    $agent = $tag['agent'] ?? null;
                    // Skip global processors (agent === null) if tools are disabled for this agent
                    if (null === $agent && $toolsDisabled) {
                        // Check if this is a tool-related processor by examining the service ID
                        if (str_contains($processorId, 'tool')) {
                            continue;
                        }
                    }
                    
                    if (null === $agent || $agent === $serviceId) {
                        $priority = $tag['priority'] ?? 0;
                        $agentOutputProcessors[] = [$priority, new Reference($processorId)];
                    }
                }
            }

            $sortCb = static fn (array $a, array $b): int => $b[0] <=> $a[0];
            usort($agentInputProcessors, $sortCb);
            usort($agentOutputProcessors, $sortCb);

            $agentDefinition = $container->getDefinition($serviceId);
            $agentDefinition
                ->setArgument(2, array_column($agentInputProcessors, 1))
                ->setArgument(3, array_column($agentOutputProcessors, 1));
        }
    }
}
