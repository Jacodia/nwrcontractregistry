<?php
// Set timezone
date_default_timezone_set('Africa/Windhoek');

// Include the notifier class
require_once 'ContractNotifier.php';

// Create instance
$notifier = new ContractNotifier();

// Define your notification schedule
$schedule = [
    ['days' => 90, 'frequency' => 'weekly'], // 3 months before expiry, weekly on Monday
    ['days' => 60, 'frequency' => 'twice'],  // 2 months before expiry, twice a week (Tue & Fri)
    ['days' => 30, 'frequency' => 'daily'],  // 1 month before expiry, daily
];

// Loop through the schedule and trigger notifications
foreach ($schedule as $item) {
    $notifier->checkAndSend($item['days'], $item['frequency']);
}

echo "Notifications executed at " . date('Y-m-d H:i:s') . "\n";
?>
