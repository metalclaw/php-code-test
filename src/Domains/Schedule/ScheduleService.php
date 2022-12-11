<?php

namespace Tymeshift\PhpTest\Domains\Schedule;

use Tymeshift\PhpTest\Components\HttpClientInterface;
use Tymeshift\PhpTest\Domains\Task\TaskFactory;
use Tymeshift\PhpTest\Domains\Task\TaskRepository;
use Tymeshift\PhpTest\Domains\Task\TaskStorage;

class ScheduleService
{
    private $scheduleRepository;
    private $taskRepository;
    public function __construct($scheduleId)
    {
        $this->scheduleId = $scheduleId;

        $scheduleStorageMock = \Mockery::mock(ScheduleStorage::class);
        $this->scheduleRepository = new ScheduleRepository($scheduleStorageMock, new ScheduleFactory());

        $scheduleStorageMock
            ->shouldReceive('getById')
            ->with($scheduleId)
            ->andReturn([
                'id' => 1,
                'start_time' => 1631232000,
                'end_time' => 1631232000 + 86400,
                'name' => 'Test'
            ]);

        $httpClientMock = \Mockery::mock(HttpClientInterface::class);
        $storage = new TaskStorage($httpClientMock);
        $this->taskRepository = new TaskRepository($storage, new TaskFactory());

        $httpClientMock->shouldReceive('request')
            ->withArgs(['GET', "/schedules/{$scheduleId}/tasks/"])
            ->andReturn([
                ["id" => 123, "schedule_id" => 1, "start_time" => 0, "duration" => 3600],
                ["id" => 431, "schedule_id" => 1, "start_time" => 3600, "duration" => 650],
                ["id" => 332, "schedule_id" => 1, "start_time" => 5600, "duration" => 3600],
            ]);

        $this->loadItemsToSchedule($this->scheduleId);
    }

    public function loadItemsToSchedule($scheduleId): \Tymeshift\PhpTest\Interfaces\EntityInterface
    {
        $schedule = $this->scheduleRepository->getById($scheduleId);
        $tasks = $this->taskRepository->getByScheduleId($scheduleId);

        foreach ($tasks as $task) {
            $schedule->addItem($task);
        }

        return $schedule;
    }
}