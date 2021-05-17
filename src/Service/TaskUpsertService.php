<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Task;

/**
 * Class TaskUpsertService
 *
 * @package App\Services\UpsertService
 */
class TaskUpsertService extends CrudUpsertService
{
    /**
     * @param Task $task
     * @return Task
     */
    public function create(Task $task): Task
    {
        $newTask = new Task();
        $this->patchScalars($newTask, $task, ['title', 'content', 'date', 'status']);

        return $newTask;
    }

    /**
     * @param Task $existingTask
     * @param Task $newTask
     * @return Task
     */
    public function patch(Task $existingTask, Task $newTask): Task
    {
        $this->patchScalars($existingTask, $newTask, ['title', 'content', 'date', 'status']);

        return $existingTask;
    }
}
