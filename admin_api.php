<?php
// admin_api.php — DEBUGGED VERSION
// Catches ALL errors and returns them as JSON so you can see what's wrong

// Catch PHP fatal errors too
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'PHP Fatal Error: ' . $e['message'],
            'file'    => $e['file'],
            'line'    => $e['line'],
            'hint'    => 'Check that config.php path is correct and no syntax errors exist'
        ]);
    }
});

// Show all errors as JSON instead of HTML blobs
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!headers_sent()) header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "PHP Error [$errno]: $errstr",
        'file'    => $errfile,
        'line'    => $errline
    ]);
    exit;
});

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// --- Load config ---
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    echo json_encode([
        'success' => false,
        'message' => 'config.php not found at: ' . $configPath,
        'hint'    => 'Make sure config.php is in the SAME folder as admin_api.php'
    ]);
    exit;
}

require_once $configPath;

if (!isset($pdo)) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed — $pdo not set',
        'hint'    => 'Check your config.php credentials and make sure MySQL is running'
    ]);
    exit;
}

// --- Create uploads folder ---
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Could not create uploads/ folder',
            'hint'    => 'Create the folder manually and chmod it to 777'
        ]);
        exit;
    }
}

// --- Check the table exists ---
try {
    $check = $pdo->query("SHOW TABLES LIKE 'activities'");
    if ($check->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Table "activities" does not exist in your database!',
            'hint'    => 'Run this SQL in phpMyAdmin: CREATE TABLE IF NOT EXISTS activities (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, activity_date DATE NOT NULL, description TEXT NOT NULL, image_path VARCHAR(500) DEFAULT NULL, status ENUM("active","archived") DEFAULT "active", archived_date DATE DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP);'
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Table check failed: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// POST — Add new activity
// ============================================================
if ($method === 'POST') {

    $title       = isset($_POST['title'])       ? trim($_POST['title'])       : '';
    $date        = isset($_POST['date'])        ? trim($_POST['date'])        : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    if (empty($title) || empty($date) || empty($description)) {
        echo json_encode([
            'success'  => false,
            'message'  => 'Please fill in all required fields (title, date, description)',
            'received' => ['title' => $title, 'date' => $date, 'description' => strlen($description) . ' chars']
        ]);
        exit;
    }

    // Handle image upload
    $imagePath = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['image'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type. Use JPG, PNG, GIF or WebP.']);
            exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Image too large. Max 5MB.']);
            exit;
        }

        $fileName   = time() . '_' . uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $imagePath = 'uploads/' . $fileName;
        }
        // If move fails, we still save without the image
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit (upload_max_filesize in php.ini)',
            UPLOAD_ERR_FORM_SIZE  => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL    => 'File only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'No temp folder configured on server',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk — check folder permissions',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by a PHP extension',
        ];
        $errCode = $_FILES['image']['error'];
        $errMsg  = $uploadErrors[$errCode] ?? 'Unknown upload error code: ' . $errCode;
        echo json_encode(['success' => false, 'message' => 'Image upload failed: ' . $errMsg]);
        exit;
    }

    // Save to database
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activities (title, activity_date, description, image_path, status, created_at)
            VALUES (:title, :date, :description, :image_path, 'active', NOW())
        ");
        $stmt->execute([
            ':title'       => $title,
            ':date'        => $date,
            ':description' => $description,
            ':image_path'  => $imagePath
        ]);

        echo json_encode([
            'success'    => true,
            'message'    => 'Activity saved successfully!',
            'id'         => (int)$pdo->lastInsertId(),
            'image_path' => $imagePath
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database INSERT error: ' . $e->getMessage(),
            'hint'    => 'Check that all column names in the activities table match what the script expects'
        ]);
    }
    exit;
}

// ============================================================
// GET — Fetch activities by status
// ============================================================
if ($method === 'GET') {
    $status = isset($_GET['status']) ? $_GET['status'] : 'active';
    if (!in_array($status, ['active', 'archived'])) $status = 'active';

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM activities
            WHERE status = :status
            ORDER BY created_at DESC
        ");
        $stmt->execute([':status' => $status]);
        $activities = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'count'   => count($activities),
            'data'    => $activities
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database SELECT error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// ============================================================
// PUT — Archive / Restore / Delete
// ============================================================
if ($method === 'PUT') {
    $rawInput = file_get_contents('php://input');
    $input    = json_decode($rawInput, true);

    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Could not parse request body as JSON',
            'raw'     => $rawInput
        ]);
        exit;
    }

    $action = isset($input['action']) ? $input['action'] : '';
    $id     = isset($input['id'])     ? (int)$input['id'] : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid activity ID: ' . $id]);
        exit;
    }

    try {
        if ($action === 'archive') {
            $stmt = $pdo->prepare("UPDATE activities SET status = 'archived', archived_date = CURDATE() WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Activity archived']);

        } elseif ($action === 'restore') {
            $stmt = $pdo->prepare("UPDATE activities SET status = 'active', archived_date = NULL WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Activity restored']);

        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("SELECT image_path FROM activities WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $activity = $stmt->fetch();

            if ($activity && !empty($activity['image_path'])) {
                $fullPath = __DIR__ . '/' . $activity['image_path'];
                if (file_exists($fullPath)) unlink($fullPath);
            }

            $stmt = $pdo->prepare("DELETE FROM activities WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Activity deleted permanently']);

        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
        }

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Fallback
echo json_encode(['success' => false, 'message' => 'Invalid request method: ' . $method]);
?>