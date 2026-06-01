<?php
// admin_api.php - FIXED VERSION

// Always output JSON
header('Content-Type: application/json');

require_once 'config.php';

// Create uploads folder if it doesn't exist
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// POST — Add new activity (multipart/form-data with image)
// ============================================================
if ($method === 'POST') {

    $title       = isset($_POST['title'])       ? trim($_POST['title'])       : '';
    $date        = isset($_POST['date'])        ? trim($_POST['date'])        : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    // Validate required fields
    if (empty($title) || empty($date) || empty($description)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all required fields (title, date, description)',
            'received' => [
                'title'       => $title,
                'date'        => $date,
                'description' => $description
            ]
        ]);
        exit;
    }

    // Handle image upload
    $imagePath = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['image'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type. Use JPG, PNG, GIF or WebP.']);
            exit;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Image too large. Maximum size is 5MB.']);
            exit;
        }

        // Safe unique filename
        $fileName  = time() . '_' . uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $imagePath = 'uploads/' . $fileName;
        } else {
            // Upload failed but don't stop — just use placeholder
            $imagePath = null;
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // An upload was attempted but failed
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE  => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL    => 'File only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'No temp folder on server',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by extension',
        ];
        $errMsg = $uploadErrors[$_FILES['image']['error']] ?? 'Unknown upload error';
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
            'id'         => $pdo->lastInsertId(),
            'image_path' => $imagePath
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// ============================================================
// GET — Fetch activities by status
// ============================================================
if ($method === 'GET') {
    $status = isset($_GET['status']) ? $_GET['status'] : 'active';

    // Validate status value
    if (!in_array($status, ['active', 'archived'])) {
        $status = 'active';
    }

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
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// ============================================================
// PUT — Archive / Restore / Delete
// ============================================================
if ($method === 'PUT') {
    $input  = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? $input['action'] : '';
    $id     = isset($input['id'])     ? (int)$input['id'] : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid activity ID']);
        exit;
    }

    try {
        if ($action === 'archive') {
            $stmt = $pdo->prepare("
                UPDATE activities
                SET status = 'archived', archived_date = CURDATE()
                WHERE id = :id
            ");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Activity archived']);

        } elseif ($action === 'restore') {
            $stmt = $pdo->prepare("
                UPDATE activities
                SET status = 'active', archived_date = NULL
                WHERE id = :id
            ");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Activity restored']);

        } elseif ($action === 'delete') {
            // Delete image file first
            $stmt = $pdo->prepare("SELECT image_path FROM activities WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $activity = $stmt->fetch();

            if ($activity && !empty($activity['image_path'])) {
                $fullPath = __DIR__ . '/' . $activity['image_path'];
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
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

// ============================================================
// Fallback
// ============================================================
echo json_encode([
    'success' => false,
    'message' => 'Invalid request method: ' . $method
]);
?>