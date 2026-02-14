<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
require_once __DIR__ . '/device_utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref = $_POST['ref'] ?? '';

    // ✅ Ajouter une note
    if (isset($_POST['add_note']) && !empty($_POST['note'])) {
        global $conn;
        $note = trim($_POST['note']);
        $user = $_SESSION['username'] ?? 'Inconnu';

        $stmt = $conn->prepare("INSERT INTO device_notes (device_ref, note, user, created_at) VALUES (?,?,?,NOW())");
        $stmt->bind_param("sss", $ref, $note, $user);
        $stmt->execute();
        $note_id = $stmt->insert_id;
        $stmt->close();

        // ✅ Upload d’une photo liée à la note
        if (!empty($_FILES['note_photo']['name'])) {
            $upload_dir = __DIR__ . "/uploads/notes/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $filename = $ref . "_note_" . time() . "_" . basename($_FILES['note_photo']['name']);
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['note_photo']['tmp_name'], $target)) {
                $webpath = "uploads/notes/" . $filename;
                $stmt2 = $conn->prepare("INSERT INTO device_note_photos (note_id, photo_path) VALUES (?, ?)");
                $stmt2->bind_param("is", $note_id, $webpath);
                $stmt2->execute();
                $stmt2->close();
            }
        }
    }

    // ✅ Mise à jour du statut
    if (!empty($_POST['status'])) {
        $status = $_POST['status'];
        global $conn;
        $stmt = $conn->prepare("UPDATE devices SET status=? WHERE ref=?");
        $stmt->bind_param("ss", $status, $ref);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: device_status.php?ref=" . urlencode($ref));
    exit;
}
