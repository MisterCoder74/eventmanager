<?php
require_once __DIR__ . '/jsonManager.php';

function getDueTasks($daysAhead = 3) {
    $tasksData = readJson(__DIR__ . '/tasks.json') ?? ['tasks' => []];
    $eventsData = readJson(__DIR__ . '/events.json') ?? ['events' => []];

    $due = [];
    $today = new DateTime();

    foreach ($tasksData['tasks'] as $task) {
        if (($task['status'] ?? 'pending') === 'done' || empty($task['due_date'])) {
            continue;
        }

        $dueDate = DateTime::createFromFormat('Y-m-d', $task['due_date']);
        if (!$dueDate) {
            continue;
        }

        $diff = (int)$today->diff($dueDate)->format('%r%a');
        if ($diff >= 0 && $diff <= $daysAhead) {
            $event = null;
            foreach ($eventsData['events'] as $evt) {
                if ($evt['id'] === $task['event_id']) {
                    $event = $evt;
                    break;
                }
            }

            if ($event) {
                $due[] = [
                    'task' => $task,
                    'event' => $event,
                    'days_until_due' => $diff
                ];
            }
        }
    }

    return $due;
}
?>
