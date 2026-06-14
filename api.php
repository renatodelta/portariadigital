<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$db_file = __DIR__ . '/database.sqlite';
$db = new PDO("sqlite:$db_file");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize DB schema
$db->exec("CREATE TABLE IF NOT EXISTS units (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    peer_id TEXT,
    last_seen INTEGER,
    status TEXT DEFAULT 'offline'
)");

$db->exec("CREATE TABLE IF NOT EXISTS deliveries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unit_id TEXT NOT NULL,
    description TEXT NOT NULL,
    courier TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at INTEGER
)");

$db->exec("CREATE TABLE IF NOT EXISTS visits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unit_id TEXT NOT NULL,
    visitor_name TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at INTEGER
)");

$db->exec("CREATE TABLE IF NOT EXISTS call_signals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unit_id TEXT NOT NULL,
    status TEXT NOT NULL, /* ringing, accepted, declined, missed */
    created_at INTEGER
)");

// Insert default houses if empty
$stmt = $db->query("SELECT COUNT(*) FROM units");
if ($stmt->fetchColumn() == 0) {
    $default_units = [
        ['101', 'Casa 101 - Família Silva'],
        ['102', 'Casa 102 - Dr. Renato'],
        ['103', 'Casa 103 - Mariana & João'],
        ['104', 'Casa 104 - Sra. Beatriz'],
        ['201', 'Casa 201 - Família Costa'],
        ['202', 'Casa 202 - Eng. Carlos']
    ];
    $insert = $db->prepare("INSERT INTO units (id, name) VALUES (?, ?)");
    foreach ($default_units as $unit) {
        $insert->execute($unit);
    }
}

// Clean old offline status (older than 10 seconds)
$db->exec("UPDATE units SET status = 'offline' WHERE last_seen < " . (time() - 10) . " AND status = 'online'");

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'ping':
        $unit_id = $_GET['unit_id'] ?? '';
        $peer_id = $_GET['peer_id'] ?? '';
        if ($unit_id) {
            $stmt = $db->prepare("UPDATE units SET peer_id = ?, last_seen = ?, status = 'online' WHERE id = ?");
            $stmt->execute([$peer_id, time(), $unit_id]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Missing unit_id']);
        }
        break;

    case 'list_units':
        $stmt = $db->query("SELECT * FROM units");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'add_delivery':
        $unit_id = $_POST['unit_id'] ?? '';
        $description = $_POST['description'] ?? '';
        $courier = $_POST['courier'] ?? '';
        if ($unit_id && $description && $courier) {
            $stmt = $db->prepare("INSERT INTO deliveries (unit_id, description, courier, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$unit_id, $description, $courier, time()]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Missing params']);
        }
        break;

    case 'list_deliveries':
        $unit_id = $_GET['unit_id'] ?? '';
        if ($unit_id) {
            $stmt = $db->prepare("SELECT * FROM deliveries WHERE unit_id = ? ORDER BY created_at DESC");
            $stmt->execute([$unit_id]);
        } else {
            $stmt = $db->query("SELECT * FROM deliveries ORDER BY created_at DESC");
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'confirm_delivery':
        $id = $_POST['id'] ?? '';
        if ($id) {
            $stmt = $db->prepare("UPDATE deliveries SET status = 'delivered' WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
        }
        break;

    case 'add_visit':
        $unit_id = $_POST['unit_id'] ?? '';
        $visitor_name = $_POST['visitor_name'] ?? '';
        if ($unit_id && $visitor_name) {
            $stmt = $db->prepare("INSERT INTO visits (unit_id, visitor_name, created_at) VALUES (?, ?, ?)");
            $stmt->execute([$unit_id, $visitor_name, time()]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Missing params']);
        }
        break;

    case 'list_visits':
        $unit_id = $_GET['unit_id'] ?? '';
        if ($unit_id) {
            $stmt = $db->prepare("SELECT * FROM visits WHERE unit_id = ? ORDER BY created_at DESC");
            $stmt->execute([$unit_id]);
        } else {
            $stmt = $db->query("SELECT * FROM visits ORDER BY created_at DESC");
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'trigger_call':
        $unit_id = $_POST['unit_id'] ?? '';
        if ($unit_id) {
            $stmt = $db->prepare("INSERT INTO call_signals (unit_id, status, created_at) VALUES (?, 'ringing', ?)");
            $stmt->execute([$unit_id, time()]);
            echo json_encode(['status' => 'success', 'call_id' => $db->lastInsertId()]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Missing unit_id']);
        }
        break;

    case 'update_call':
        $call_id = $_POST['call_id'] ?? '';
        $status = $_POST['status'] ?? '';
        if ($call_id && $status) {
            $stmt = $db->prepare("UPDATE call_signals SET status = ? WHERE id = ?");
            $stmt->execute([$status, $call_id]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Missing params']);
        }
        break;

    // Server-Sent Events for real-time notifications on the resident app
    case 'events':
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        $unit_id = $_GET['unit_id'] ?? '';
        if (!$unit_id) {
            echo "data: " . json_encode(['error' => 'No unit specified']) . "\n\n";
            exit;
        }

        // Loop to send events
        $last_delivery_id = 0;
        $last_visit_id = 0;
        $last_call_id = 0;

        // Fetch current max ids first so we only send new ones
        $stmt = $db->prepare("SELECT MAX(id) FROM deliveries WHERE unit_id = ?");
        $stmt->execute([$unit_id]);
        $last_delivery_id = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT MAX(id) FROM visits WHERE unit_id = ?");
        $stmt->execute([$unit_id]);
        $last_visit_id = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT MAX(id) FROM call_signals WHERE unit_id = ?");
        $stmt->execute([$unit_id]);
        $last_call_id = (int)$stmt->fetchColumn();

        while (true) {
            // Check for new deliveries
            $stmt = $db->prepare("SELECT * FROM deliveries WHERE unit_id = ? AND id > ? AND status = 'pending' ORDER BY id ASC");
            $stmt->execute([$unit_id, $last_delivery_id]);
            $new_deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($new_deliveries as $del) {
                echo "event: delivery\n";
                echo "data: " . json_encode($del) . "\n\n";
                $last_delivery_id = max($last_delivery_id, $del['id']);
            }

            // Check for new visits
            $stmt = $db->prepare("SELECT * FROM visits WHERE unit_id = ? AND id > ? AND status = 'pending' ORDER BY id ASC");
            $stmt->execute([$unit_id, $last_visit_id]);
            $new_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($new_visits as $vis) {
                echo "event: visit\n";
                echo "data: " . json_encode($vis) . "\n\n";
                $last_visit_id = max($last_visit_id, $vis['id']);
            }

            // Check for new call signals (in case of background call simulation)
            $stmt = $db->prepare("SELECT * FROM call_signals WHERE unit_id = ? AND id > ? AND status = 'ringing' AND created_at > ? ORDER BY id ASC");
            $stmt->execute([$unit_id, $last_call_id, time() - 30]); // only recent calls
            $new_calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($new_calls as $call) {
                echo "event: incoming_call\n";
                echo "data: " . json_encode($call) . "\n\n";
                $last_call_id = max($last_call_id, $call['id']);
            }

            ob_flush();
            flush();
            sleep(1);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
