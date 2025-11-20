<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Store\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Store\Command\SetupStoreCommand;
use Symfony\AI\Store\Exception\RuntimeException;
use Symfony\AI\Store\ManagedStoreInterface;
use Symfony\AI\Store\StoreInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class SetupStoreCommandTest extends TestCase
{
    public function testCommandIsConfigured()
    {
        $command = new SetupStoreCommand(new ServiceLocator([]));

        $this->assertSame('ai:store:setup', $command->getName());
        $this->assertSame('Prepare the required infrastructure for the store', $command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('store'));

        $storeArgument = $definition->getArgument('store');
        $this->assertSame('Name of the store to setup', $storeArgument->getDescription());
        $this->assertTrue($storeArgument->isRequired());

        $this->assertTrue($definition->hasOption('option'));

        $optionOption = $definition->getOption('option');
        $this->assertSame('Pass an option to the store setup (multiple values allowed)', $optionOption->getDescription());
        $this->assertTrue($optionOption->isArray());
    }

    public function testCommandCannotSetupUndefinedStore()
    {
        $command = new SetupStoreCommand(new ServiceLocator([]));

        $tester = new CommandTester($command);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('The "foo" store does not exist.');
        self::expectExceptionCode(0);
        $tester->execute([
            'store' => 'foo',
        ]);
    }

    public function testCommandCannotSetupInvalidStore()
    {
        $store = $this->createMock(StoreInterface::class);

        $command = new SetupStoreCommand(new ServiceLocator([
            'foo' => static fn (): object => $store,
        ]));

        $tester = new CommandTester($command);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('The "foo" store does not support setup.');
        self::expectExceptionCode(0);
        $tester->execute([
            'store' => 'foo',
        ]);
    }

    public function testCommandCannotSetupStoreWithException()
    {
        $store = $this->createMock(ManagedStoreInterface::class);
        $store->expects($this->once())->method('setup')->willThrowException(new RuntimeException('foo'));

        $command = new SetupStoreCommand(new ServiceLocator([
            'foo' => static fn (): object => $store,
        ]));

        $tester = new CommandTester($command);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('An error occurred while setting up the "foo" store: foo');
        self::expectExceptionCode(0);
        $tester->execute([
            'store' => 'foo',
        ]);
    }

    public function testCommandCanSetupDefinedStore()
    {
        $store = $this->createMock(ManagedStoreInterface::class);
        $store->expects($this->once())->method('setup')->with([]);

        $command = new SetupStoreCommand(new ServiceLocator([
            'foo' => static fn (): object => $store,
        ]));

        $tester = new CommandTester($command);

        $tester->execute([
            'store' => 'foo',
        ]);

        $this->assertStringContainsString('The "foo" store was set up successfully.', $tester->getDisplay());
    }

    public function testCommandCanSetupDefinedStoreWithOptions()
    {
        $store = $this->createMock(ManagedStoreInterface::class);
        $store->expects($this->once())->method('setup')->with([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);

        $command = new SetupStoreCommand(new ServiceLocator([
            'foo' => static fn (): object => $store,
        ]));

        $tester = new CommandTester($command);

        $tester->execute([
            'store' => 'foo',
            '--option' => ['key1=value1', 'key2=value2'],
        ]);

        $this->assertStringContainsString('The "foo" store was set up successfully.', $tester->getDisplay());
    }

    public function testCommandThrowsExceptionForInvalidOptionFormat()
    {
        $store = $this->createMock(ManagedStoreInterface::class);

        $command = new SetupStoreCommand(new ServiceLocator([
            'foo' => static fn (): object => $store,
        ]));

        $tester = new CommandTester($command);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Invalid option format: "invalid". Expected format: key=value');
        $tester->execute([
            'store' => 'foo',
            '--option' => ['invalid'],
        ]);
    }
}
