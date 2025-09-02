<?php

namespace App\Command;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:test-clock',
    description: 'Test the clock agent with GPT-4.0 mini',
)]
class TestClockCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'ai.agent.clock')]
        private AgentInterface $clockAgent,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Testing Clock Agent with GPT-4.0 mini');
        
        $io->section('Asking for current date and time...');
        
        $messages = new MessageBag();
        $messages->add(Message::ofUser('What is the current date and time?'));
        
        $result = $this->clockAgent->call($messages);
        
        \assert($result instanceof TextResult);
        
        $io->success('Response from Clock Agent:');
        $io->writeln($result->getContent());

        return Command::SUCCESS;
    }
}