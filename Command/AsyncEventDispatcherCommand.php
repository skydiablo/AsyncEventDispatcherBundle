<?php


namespace SkyDiablo\AsyncEventDispatcherBundle\Command;

use Psr\Log\LoggerInterface;
use SkyDiablo\AsyncEventDispatcherBundle\Service\QueueWorkerService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description for class AsyncEventDispatcherCommand
 */
class AsyncEventDispatcherCommand extends ContainerAwareCommand
{

    const COMMAND_NAME = 'aws_eb_async_event_dispatcher';
    const DEFAULT_ITERATE_AMOUNT = 10;
    const OPTION_ITERATE_AMOUNT = 'iterate-amount';
    const OPTION_SLEEP = 'sleep';

    /**
     * @var LoggerInterface
     */
    private $logger;

    protected function configure()
    {
        parent::configure();
        $this
            ->setName(self::COMMAND_NAME)
            ->addOption(self::OPTION_ITERATE_AMOUNT, null, InputOption::VALUE_OPTIONAL, null, self::DEFAULT_ITERATE_AMOUNT)
            ->addOption(self::OPTION_SLEEP, null, InputOption::VALUE_OPTIONAL, null, 60);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->logger = $this->getContainer()->get('logger');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sleep = (int)$input->getOption(self::OPTION_SLEEP) ?: 60;
        /** @var QueueWorkerService $queueWorkerService */
        $queueWorkerService = $this->getContainer()->get('async_event_dispatcher.service.queue_worker');
        $longRunCleaner = $this->getContainer()->get('long_running.delegating_cleaner');
        try {
            while (true) {
                $result = $queueWorkerService->run((int)$input->getOption(self::OPTION_ITERATE_AMOUNT) ?: self::DEFAULT_ITERATE_AMOUNT);
                if (!$result) {
                    sleep($sleep);
                }
                $longRunCleaner->cleanUp();
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('[ERROR] While execute "%s" command: %s', $this->getName(), $e->getMessage()), [$e]);
        }
        return 0;
    }
}