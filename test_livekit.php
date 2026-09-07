<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LiveSession;
use App\Services\LiveKitService;

try {
    $session = new LiveSession();
    $session->fill([
        'title' => 'Test LiveKit Session',
        'course_id' => 1,
        'instructor_id' => 1,
        'start_at' => '2026-09-05 10:00:00',
        'duration' => 60,
        'provider' => 'livekit',
        'max_participants' => 10,
    ]);
    $session->status = 'Scheduled';
    $session->created_by = 1;

    $liveKitService = new LiveKitService();
    $roomData = $liveKitService->createRoom($session);

    $session->meeting_id = $roomData['id'];
    $session->meeting_url = $roomData['join_url'];
    $session->host_url = $roomData['start_url'];
    $session->save();

    echo "SUCCESS: Session created with ID {$session->id}\n";
    echo "Room: {$roomData['id']}\n";
    echo "Join URL: {$roomData['join_url']}\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
