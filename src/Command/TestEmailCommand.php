<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:test:email',
    description: 'Test email sending functionality'
)]
class TestEmailCommand extends Command
{
    public function __construct(
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Testing email sending...</info>');

        try {
            $email = (new Email())
                ->from('chaabanisarrra@gmail.com')
                ->to('test@example.com')
                ->subject('Test Email from E-Sport')
                ->text('This is a test email to verify the mailer configuration.')
                ->html('<p>This is a test email to verify the mailer configuration.</p>');

            $this->mailer->send($email);

            $output->writeln('<fg=green>✓ Test email sent successfully!</fg=green>');
            $output->writeln('<info>Please check your Mailtrap inbox.</info>');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<fg=red>✗ Error sending email:</fg=red>');
            $output->writeln('<fg=red>' . $e->getMessage() . '</fg=red>');

            return Command::FAILURE;
        }
    }
}
