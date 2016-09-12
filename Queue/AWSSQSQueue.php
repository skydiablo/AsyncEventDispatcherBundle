<?php


namespace AsyncEventDispatcherBundle\Queue;

use Aws\Sqs\SqsClient;
use Psr\Log\LoggerInterface;


/**
 * Description for class AWSSQSQueue
 */
class AWSSQSQueue implements QueueInterface {

    const AWS_SQS_KEY_QUEUE_URL = 'QueueUrl';
    const AWS_SQS_KEY_RECEIPT_HANDLE = 'ReceiptHandle';
    const AWS_SQS_KEY_BODY = 'Body';
    const STORAGE_EVENT_NAME = 'en';
    const STORAGE_EVENT_DATA = 'ed';

    /**
     * @var SqsClient
     */
    private $sqs;

    /**
     * @var string
     */
    private $queueUrl;

    /**
     * @var int seconds
     */
    private $longPollingTimeout = 10;

    /**
     * cache all data to run all at one on destroy this object
     * @var string[]
     */
    private $batch = [];

    /**
     * @var bool
     */
    private $isCliMode;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SqsClient $sqs
     * @param string $queueUrl
     * @param LoggerInterface $logger
     */
    function __construct(SqsClient $sqs, $queueUrl, LoggerInterface $logger) {
        $this->sqs = $sqs;
        $this->queueUrl = (string)$queueUrl;
        $this->isCliMode = php_sapi_name() == 'cli';
        $this->logger = $logger;
    }

    /**
     * destructor trigger all queued events
     */
    function __destruct() {
        $this->flushBatch();
    }

    public function flushBatch() {
        if($this->batch) {
            try {
                $this->sendBatch($this->batch);
            } catch (\Exception $e) {
                $this->logger->error(sprintf('Cannot batch queue to aws sqs: %s', $e->getMessage()), [$e, $this]);
            }
        }
    }

    /**
     * @return int
     */
    public function getLongPollingTimeout() {
        return $this->longPollingTimeout;
    }

    /**
     * @param int $longPollingTimeout
     */
    public function setLongPollingTimeout($longPollingTimeout) {
        $this->longPollingTimeout = (int)$longPollingTimeout;
    }

    /**
     * @param QueueItemInterface $queueItem
     * @return bool
     */
    public function add(QueueItemInterface $queueItem) {
        $messageBody = base64_encode(gzcompress(serialize($queueItem)));

        if ($this->isCliMode) { // fire instantly in CLI mode
            try {
                $this->sendBatch([$messageBody]);
            } catch (\Exception $e) {
                $this->logger->error(sprintf('Cannot add queue item to aws sqs queue: %s', $e->getMessage()), [$e, $this, $queueItem]);
                return false;
            }
        } else {
            $this->batch[] = $messageBody; //queue message
        }

        return true;
    }

    /**
     * send message to AWS SQS in batch mode
     * @param array $messageBodies
     * @return \Aws\Result
     */
    protected function sendBatch(array $messageBodies) {
        $resultModel = $this->sqs->sendMessageBatch([
            self::AWS_SQS_KEY_QUEUE_URL => $this->queueUrl,
            'Entries' => array_map(function ($messageBody, $key) {
                return [
                    'Id' => $key,
                    'MessageBody' => $messageBody
                ];
            }, $messageBodies, array_keys($messageBodies))
        ]);
        return $resultModel;
    }

    /**
     * @param int $maxCount
     * @return QueueItemInterface[]
     */
    public function pull($maxCount = 10) {

        $result = [];

        try {
            $response = $this->sqs->receiveMessage([
                'MaxNumberOfMessages' => (int)$maxCount,
                'WaitTimeSeconds' => (int)$this->longPollingTimeout,
                self::AWS_SQS_KEY_QUEUE_URL => (string)$this->queueUrl
            ]);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Cannot pull from aws sqs queue: %s', $e->getMessage()), [$e, $this]);
            return $result;
        }

        $messages = (array)$response->get('Messages');

        foreach ($messages as $sqsMessage) {
            if (isset($sqsMessage[self::AWS_SQS_KEY_BODY]) || isset($sqsMessage[self::AWS_SQS_KEY_RECEIPT_HANDLE])) {
                $queueItem = unserialize(gzuncompress(base64_decode((string)$sqsMessage[self::AWS_SQS_KEY_BODY])));

                // legacy fallback
                if (is_array($queueItem) && isset($queueItem[self::STORAGE_EVENT_NAME]) && isset($queueItem[self::STORAGE_EVENT_DATA])) {
                    $queueItem = new QueueItem($queueItem[self::STORAGE_EVENT_NAME], $queueItem[self::STORAGE_EVENT_DATA]);
                }

                if ($queueItem instanceof QueueItemInterface) {
                    $queueItem->setQueueIdentifier($sqsMessage[self::AWS_SQS_KEY_RECEIPT_HANDLE]);
                    $result[] = $queueItem;
                } else {
                    $this->logger->warning(sprintf('Invalid message, have to be a QueueItem, given "%s"', is_object($queueItem) ? get_class($queueItem) : $queueItem), ['QueueItem' => $queueItem]);
                }
            } else {
                $this->logger->warning('Invalid response from AWS SQS queue', [$sqsMessage]);
            }
        }

        return $result;
    }

    /**
     * @param QueueItemInterface $queueItem
     * @return mixed
     */
    public function remove(QueueItemInterface $queueItem) {
        try {
            $this->sqs->deleteMessage([
                self::AWS_SQS_KEY_QUEUE_URL => $this->queueUrl,
                self::AWS_SQS_KEY_RECEIPT_HANDLE => $queueItem->getQueueIdentifier()
            ]);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Cannot delete queue item from aws sqs queue: %s', $e->getMessage()), [$e, $this, $queueItem]);
            return false;
        }
        return true;
    }
}