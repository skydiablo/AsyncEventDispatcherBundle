<?php


namespace SkyDiablo\AsyncEventDispatcherBundle\Command;

use SkyDiablo\AWSBundle\Command\ElasticBeanstalk\SingleInstanceCommand;
use Psr\Log\LoggerInterface;
use SkyDiablo\AsyncEventDispatcherBundle\Service\QueueWorkerService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Volker von Hoesslin <volker@oksnap.me>
 * Class AWSEBAsyncEventDispatcherCommand
 */
class AWSEBAsyncEventDispatcherCommand extends SingleInstanceCommand
{

    const COMMAND_NAME = 'aws_eb_async_event_dispatcher';
    const OPTION_ITERATE_AMOUNT = 'iterate-amount';
    const DEFAULT_ITERATE_AMOUNT = 10;

    /**
     * @var LoggerInterface
     */
    private $logger;

    protected function configure()
    {
        parent::configure();
        $this->setName(self::COMMAND_NAME);
        $this->addOption(self::OPTION_ITERATE_AMOUNT, null, InputOption::VALUE_OPTIONAL, null, self::DEFAULT_ITERATE_AMOUNT);
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
        /** @var QueueWorkerService $queueWorkerService */
        $queueWorkerService = $this->getContainer()->get('async_event_dispatcher.service.queue_worker');
        try {
            $queueWorkerService->run((int)$input->getOption(self::OPTION_ITERATE_AMOUNT) ?: self::DEFAULT_ITERATE_AMOUNT);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('[ERROR] While execute "%s" command: %s', $this->getName(), $e->getMessage()), [$e]);
        }
        return 0;
    }

}