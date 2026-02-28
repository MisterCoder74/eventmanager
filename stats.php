<?php
require_once __DIR__ . '/jsonManager.php';

function getDashboardStats() {
    $eventsData = readJson(__DIR__ . '/events.json') ?? ['events' => []];
    $tasksData = readJson(__DIR__ . '/tasks.json') ?? ['tasks' => []];

    $totalEvents = count($eventsData['events']);
    $today = date('Y-m-d');
    $upcomingEvents = 0;
    $totalBudget = 0;

    foreach ($eventsData['events'] as $event) {
        if (!empty($event['date']) && $event['date'] >= $today) {
            $upcomingEvents++;
        }

        // Calculate total budget from events
        if (!empty($event['budget_client'])) {
            $totalBudget += (float)$event['budget_client'];
        }
    }

    $pendingTasks = 0;
    foreach ($tasksData['tasks'] as $task) {
        if (($task['status'] ?? 'pending') !== 'done') {
            $pendingTasks++;
        }
    }

    return [
        'total_events' => $totalEvents,
        'upcoming_events' => $upcomingEvents,
        'pending_tasks' => $pendingTasks,
        'total_planned_budget' => $totalBudget
    ];
}
?>
