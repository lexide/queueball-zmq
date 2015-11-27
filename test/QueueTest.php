<?php
/**
 * @package queueball-zmq
 */
namespace Silktide\QueueBall\ZeroMq\Test;

use Silktide\QueueBall\ZeroMq\Queue;

class QueueTest extends \PHPUnit_Framework_TestCase {

    protected $context;

    /**
     * @var \Mockery\Mock|\ZMQSocket
     */
    protected $socket;

    protected $messageFactory;

    protected $queueMessage;

    public function setUp()
    {
        $this->socket = \Mockery::mock("\\ZMQSocket");

        $this->context = \Mockery::mock("Silktide\\QueueBall\\ZeroMq\\SocketFactory");
        $this->context->shouldReceive("createPushSocket")->atMost()->once()->andReturn($this->socket);
        $this->context->shouldReceive("createPullSocket")->atMost()->once()->andReturn($this->socket);

        $this->queueMessage = \Mockery::mock("Silktide\\QueueBall\\Message\\QueueMessage");

        $this->messageFactory = \Mockery::mock("Silktide\\QueueBall\\Message\\QueueMessageFactoryInterface");
        $this->messageFactory->shouldReceive("createMessage")->andReturn($this->queueMessage);
    }

    /**
     * @dataProvider queueIdProvider
     *
     * @param $existingQueueId
     */
    public function testPushSocketIsSetup($existingQueueId)
    {
        $queueId = "queueId";

        $endpoints = [
            "bind" => [],
            "connect" => []
        ];
        if (!empty($existingQueueId)) {
            $endpoints["connect"][] = $existingQueueId;
        }

        $queueIdIsTheSame = $existingQueueId == $queueId;

        $this->socket->shouldReceive("getEndpoints")->andReturn($endpoints);
        $this->socket->shouldReceive("connect")->times(1 + (int) $queueIdIsTheSame);
        $this->socket->shouldReceive("send")->twice();
        if (!$queueIdIsTheSame) {
            $this->socket->shouldReceive("disconnect")->withArgs([$existingQueueId])->atLeast()->once();
        }

        $queue = new Queue($this->context, $this->messageFactory);
        $queue->sendMessage("blah", $existingQueueId);
        $queue->sendMessage("blah", $queueId);
    }

    /**
     * @dataProvider queueIdProvider
     *
     * @param $existingQueueId
     */
    public function testPullSocketIsSetup($existingQueueId)
    {
        $queueId = "queueId";

        $endpoints = [
            "bind" => [],
            "connect" => []
        ];
        if (!empty($existingQueueId)) {
            $endpoints["bind"][] = $existingQueueId;
        }

        $queueIdIsTheSame = $existingQueueId == $queueId;

        $this->socket->shouldReceive("getEndpoints")->andReturn($endpoints);
        $this->socket->shouldReceive("bind")->times(1 + (int) $queueIdIsTheSame);
        $this->socket->shouldReceive("recv")->twice();
        if (!$queueIdIsTheSame) {
            $this->socket->shouldReceive("unbind")->withArgs([$existingQueueId])->atLeast()->once();
        }

        $queue = new Queue($this->context, $this->messageFactory);
        $queue->receiveMessage("blah", $existingQueueId);
        $queue->receiveMessage("blah", $queueId);
    }

    public function queueIdProvider()
    {
        return [
            ["queueId"],
            ["differentQueueId"]
        ];
    }

}
 