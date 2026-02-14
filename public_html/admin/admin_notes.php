<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: /../login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';
require_once __DIR__ . '/../sync_time.php';

/* Connexion MySQL */
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=u498346438_calculrem;charset=utf8mb4",
        "u498346438_calculrem",
        "Calculrem1",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erreur MySQL");
}

/* Upload + sauvegarde note */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    $fileName = null;
    $filePath = null;

    if (!empty($_FILES['document']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/admin_notes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = time() . '_' . basename($_FILES['document']['name']);
        $filePath = $uploadDir . $fileName;

        move_uploaded_file($_FILES['document']['tmp_name'], $filePath);
    }

    $stmt = $pdo->prepare("
        INSERT INTO admin_notes (title, content, file_name, file_path, created_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $content, $fileName, $filePath, $username]);

    header("Location: admin_notes.php");
    exit;
}

/* Récupération des notes */
$notes = $pdo->query("
    SELECT * FROM admin_notes
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Notes Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="/../assets/css/dashboard.css">
</head>

<body>

<div class="container py-4 pb-5">
<?php include '../header.php'; ?>

<h2 class="text-center fw-bold my-4">📝 Notes Administrateur</h2>

<!-- FORMULAIRE -->
<div class="card mb-4">
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Titre</label>
        <input type="text" name="title" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Note</label>
        <textarea name="content" class="form-control" rows="4"></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Document (optionnel)</label>
        <input type="file" name="document" class="form-control">
      </div>

      <button class="btn btn-primary w-100">
        <i class="bi bi-save"></i> Enregistrer
      </button>
    </form>
  </div>
</div>

<!-- LISTE DES NOTES -->
<?php foreach ($notes as $note): ?>
<div class="card mb-3">
  <div class="card-body">
    <h5><?= htmlspecialchars($note['title']) ?></h5>
    <p><?= nl2br(htmlspecialchars($note['content'])) ?></p>

    <?php if ($note['file_name']): ?>
      <a class="btn btn-sm btn-outline-primary"
         href="/uploads/admin_notes/<?= urlencode($note['file_name']) ?>"
         download>
         <i class="bi bi-download"></i> Télécharger
      </a>
    <?php endif; ?>

    <div class="text-muted small mt-2">
      Ajouté par <?= htmlspecialchars($note['created_by']) ?> — <?= $note['created_at'] ?>
    </div>
  </div>
</div>
<?php endforeach; ?>

</div>

</body>
</html>
