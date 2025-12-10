<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Console\Command;

use PushON\LiveSearchReadOnly\HealthCheck\CheckInterface;
use PushON\LiveSearchReadOnly\HealthCheck\CheckResult;
use PushON\LiveSearchReadOnly\HealthCheck\StatusLine;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Health check command for LiveSearch ReadOnly configuration
 */
class HealthCheckCommand extends Command
{
    /**
     * @param CheckInterface[] $checks
     */
    public function __construct(
        private readonly array $checks
    ) {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('pushon:livesearch-readonly:health');
        $this->setDescription('Health check for LiveSearch ReadOnly configuration');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->printHeader($output);

        $allPassed = true;
        foreach ($this->checks as $check) {
            $result = $check->execute();
            $this->printResult($output, $result);

            if (!$result->isPassed()) {
                $allPassed = false;
            }
        }

        $this->printSummary($output, $allPassed);

        return $allPassed ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Print header
     *
     * @param OutputInterface $output
     * @return void
     */
    private function printHeader(OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln('<fg=cyan>╔══════════════════════════════════════════════════════════════╗</>');
        $line = '<fg=cyan>║</>  <fg=white;options=bold>LiveSearch ReadOnly - Health Check</>  ';
        $output->writeln($line . '                       <fg=cyan>║</>');
        $output->writeln('<fg=cyan>╚══════════════════════════════════════════════════════════════╝</>');
        $output->writeln('');
    }

    /**
     * Print check result
     *
     * @param OutputInterface $output
     * @param CheckResult $result
     * @return void
     */
    private function printResult(OutputInterface $output, CheckResult $result): void
    {
        if (empty($result->getStatuses()) && empty($result->getMessages())) {
            return;
        }

        $output->writeln('<fg=yellow>' . $result->getTitle() . '</>');
        $output->writeln(str_repeat('─', 64));

        foreach ($result->getStatuses() as $status) {
            $this->printStatusLine($output, $status);
        }

        foreach ($result->getMessages() as $message) {
            $this->printMessage($output, $message);
        }

        $output->writeln('');
    }

    /**
     * Print status line
     *
     * @param OutputInterface $output
     * @param StatusLine $status
     * @return void
     */
    private function printStatusLine(OutputInterface $output, StatusLine $status): void
    {
        $icon = $status->isOk()
            ? '<fg=green>✓</>'
            : ($status->isCritical() ? '<fg=red>✗</>' : '<fg=yellow>○</>');

        $label = str_pad($status->getLabel(), 20);

        if ($status->getValue() !== null) {
            $valueColor = $status->isOk() ? 'white' : ($status->isCritical() ? 'red' : 'yellow');
            $output->writeln(sprintf(
                '  %s %s <fg=%s>%s</>',
                $icon,
                $label,
                $valueColor,
                $status->getValue()
            ));
        } else {
            $output->writeln(sprintf('  %s %s', $icon, $label));
        }
    }

    /**
     * Print message
     *
     * @param OutputInterface $output
     * @param string $message
     * @return void
     */
    private function printMessage(OutputInterface $output, string $message): void
    {
        if (str_starts_with($message, 'error:')) {
            $output->writeln('  <fg=red>└─ ' . substr($message, 6) . '</>');
        } elseif (str_starts_with($message, 'help:')) {
            $output->writeln('');
            $output->writeln('  <fg=yellow>' . substr($message, 5) . '</>');
        } elseif (str_starts_with($message, 'cmd:')) {
            $output->writeln('     <fg=white>' . substr($message, 4) . '</>');
        } else {
            $output->writeln('  <fg=gray>└─ ' . $message . '</>');
        }
    }

    /**
     * Print summary
     *
     * @param OutputInterface $output
     * @param bool $passed
     * @return void
     */
    private function printSummary(OutputInterface $output, bool $passed): void
    {
        $output->writeln(str_repeat('═', 64));

        if ($passed) {
            $output->writeln('<fg=green;options=bold>✓ All checks passed</> - LiveSearch should work correctly');
        } else {
            $output->writeln('<fg=red;options=bold>✗ Some checks failed</> - Review issues above');
        }

        $output->writeln('');
    }
}
