<?php
declare(strict_types=1);

namespace Tests;

use Codeception\Example;
use Codeception\Test\Unit;
use Mockery\Mock;
use Tymeshift\PhpTest\Components\HttpClientInterface;
use Tymeshift\PhpTest\Domains\Task\TaskCollection;
use Tymeshift\PhpTest\Domains\Task\TaskEntity;
use Tymeshift\PhpTest\Domains\Task\TaskFactory;
use Tymeshift\PhpTest\Domains\Task\TaskRepository;
use Tymeshift\PhpTest\Domains\Task\TaskStorage;

class TaskCest
{

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var HttpClientInterface
     */
    private $httpClientMock;

    public function _before()
    {
        $this->httpClientMock = \Mockery::mock(HttpClientInterface::class);
        $storage = new TaskStorage($this->httpClientMock);
        $this->taskRepository = new TaskRepository($storage, new TaskFactory());
    }

    public function _after()
    {
        $this->taskRepository = null;
        \Mockery::close();
    }

    /**
     * @dataProvider tasksDataProvider
     */
    public function testGetTasks(Example $example, \UnitTester $tester)
    {
        $scheduleId = 1;

        $this->httpClientMock->shouldReceive('request')
            ->withArgs(['GET', "/schedules/{$scheduleId}/tasks/"])
            ->andReturn((array)$example);

        $tasks = $this->taskRepository->getByScheduleId($scheduleId);
        $tester->assertInstanceOf(TaskCollection::class, $tasks);
    }

    public function testGetTasksFailed(\UnitTester $tester)
    {
        $scheduleId = 4;

        $this->httpClientMock
            ->shouldReceive('request')
            ->withArgs(['GET',"/schedules/{$scheduleId}/tasks/"])
            ->andReturn([]);

        $tester->expectThrowable(\Exception::class, function () use ($scheduleId) {
            $this->taskRepository->getByScheduleId($scheduleId);
        });
    }

    /**
     * @dataProvider taskDataProvider
     */
    public function testGetTask(Example $example, \UnitTester $tester)
    {
        ['id' => $id] = $example;

        $this->httpClientMock->shouldReceive('request')
            ->withArgs(['GET', "/tasks/{$id}"])
            ->andReturn((array)$example);

        $task = $this->taskRepository->getById($id);
        $tester->assertInstanceOf(TaskEntity::class, $task);
    }

    public function testGetTaskFailed(\UnitTester $tester)
    {
        $id = 900;

        $this->httpClientMock
            ->shouldReceive('request')
            ->withArgs(['GET',"/tasks/{$id}"])
            ->andReturn([]);

        $tester->expectThrowable(\Exception::class, function () use ($id) {
            $this->taskRepository->getById($id);
        });
    }

    public function tasksDataProvider()
    {
        return [
            [
                ["id" => 123, "schedule_id" => 1, "start_time" => 0, "duration" => 3600],
                ["id" => 431, "schedule_id" => 1, "start_time" => 3600, "duration" => 650],
                ["id" => 332, "schedule_id" => 1, "start_time" => 5600, "duration" => 3600],
            ]
        ];
    }

    public function taskDataProvider()
    {
        return [
            ["id" => 123, "schedule_id" => 1, "start_time" => 0, "duration" => 3600],
        ];
    }
}