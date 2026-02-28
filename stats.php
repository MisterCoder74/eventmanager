<?php
require_once __DIR__ . '/jsonManager.php';

function getDashboardStats() {
    $eventsData = readJson(__DIR__ . '/events.json') ?? ['events' => []];
    $tasksData = readJson(__DIR__ . '/tasks.json') ?? ['tasks' => []];
    $budgetData = readJson(__DIR__ . '/budget.json') ?? ['items' => []];

    $totalEvents = count($eventsData['events']);
    $today = date('Y-m-d');
    $upcomingEvents = 0;

    foreach ($eventsData['events'] as $event) {
        if (!empty($event['date']) && $event['date'] >= $today) {
            $upcomingEvents++;
        }
    }

    $pendingTasks = 0;
    foreach ($tasksData['tasks'] as $task) {
        if (($task['status'] ?? 'pending') !== 'done') {
            $pendingTasks++;
        }
    }

    $plannedBudget = 0;
    foreach ($budgetData['items'] as $item) {
        if (($item['type'] ?? 'planned') === 'planned') {
            $plannedBudget += (float)($item['amount'] ?? 0);
        }
    }

    return [
        'total_events' => $totalEvents,
        'upcoming_events' => $upcomingEvents,
        'pending_tasks' => $pendingTasks,
        'total_planned_budget' => $plannedBudget
    ];
}
?>
