<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /../login.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Utilisateur';

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

/* Envoi message */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $message = trim($_POST['message'] ?? '');
    $fileName = null;
    $fileType = null;

    if (!empty($_FILES['file']['name'])) {

        $uploadDir = __DIR__ . '/../uploads/chat/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = time().'_'.basename($_FILES['file']['name']);
        $fileType = mime_content_type($_FILES['file']['tmp_name']);

        move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir.$fileName);
    }

    if ($message !== '' || $fileName) {
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (user_id, username, message, file_name, file_type)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $username, $message, $fileName, $fileType]);
    }

    header("Location: chat.php");
    exit;
}
$isMe = ($msg['user_id'] == $_SESSION['user_id']);

/* Messages */
$messages = $pdo->query("
    SELECT * FROM chat_messages
    ORDER BY created_at ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Chat interne</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="/../assets/css/dashboard.css">
<div class="container py-4 pb-5 chat-page">

<div class="chat-page">
    <!-- TOUT ton contenu chat ici -->

<style>
.chat-box {
    max-height: 65vh;
    overflow-y: auto;
}
.chat-message {
    border-radius: 12px;
    padding: 10px;
    margin-bottom: 10px;
    background: #f8f9fa;
}
.chat-user {
    font-weight: bold;
}
.chat-time {
    font-size: 12px;
    color: #777;
}
.chat-img {
    max-width: 150px;
    border-radius: 8px;
}
/* =========================
   CHAT — FORCE MODE CLAIR
   ========================= */
.chat-page {
    all: initial;
    font-family: inherit;
}

.chat-page {
    color: #111 !important;
}

/* Titre */
.chat-page h2 {
    color: #111 !important;
}

/* Zone messages */
.chat-box {
    background: transparent !important;
}

/* Message */
.chat-message {
    background: #ffffff !important;
    color: #111 !important;
    border: 1px solid #e5e7eb;
}

/* Tout texte interne */
.chat-message * {
    color: #111 !important;
}

/* Nom utilisateur */
.chat-user {
    color: #0d6efd !important;
    font-weight: 600;
}

/* Date */
.chat-time {
    color: #6b7280 !important;
}

/* Carte formulaire */
.chat-page .card {
    background: #ffffff !important;
    color: #111 !important;
}

/* Inputs */
.chat-page textarea,
.chat-page input,
.chat-page select {
    background: #ffffff !important;
    color: #111 !important;
    border: 1px solid #ced4da;
}

/* Placeholder */
.chat-page textarea::placeholder {
    color: #6b7280 !important;
}

/* Images */
.chat-img {
    border: 1px solid #ddd;
    background: #fff;
}
/* =========================
   CHAT — STYLE DASHBOARD
   ========================= */

.chat-page {
    background: #f4f6f9;   /* fond dashboard */
    min-height: 100vh;
    padding-bottom: 80px;
}

/* Titre */
.chat-page h2 {
    color: #1f2937;
}

/* Zone messages */
.chat-box {
    max-width: 900px;
    margin: 0 auto 20px auto;
}

/* Carte message */
.chat-message {
    background: #ffffff;
    border-radius: 12px;
    padding: 12px 14px;
    margin-bottom: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}

/* Nom */
.chat-user {
    color: #0d6efd;
    font-weight: 600;
    margin-bottom: 2px;
}

/* Texte */
.chat-message p,
.chat-message div {
    color: #111827;
}

/* Date */
.chat-time {
    font-size: 12px;
    color: #6b7280;
}

/* Formulaire */
.chat-page form.card {
    max-width: 900px;
    margin: 0 auto;
    border-radius: 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

/* Champs */
.chat-page textarea,
.chat-page input[type="file"] {
    border-radius: 10px;
}

/* Bouton envoyer */
.chat-page .btn-primary {
    border-radius: 12px;
    font-weight: 600;
}
.chat-message.me {
    background: #e7f1ff;
    border-left: 4px solid #0d6efd;
}

</style>
</head>

<body>
<div class="chat-message <?= $isMe ? 'me' : '' ?>">

<div class="container py-4 pb-5">
<?php include '../header.php'; ?>

<h2 class="text-center fw-bold my-3">💬 Communication interne</h2>

<div class="chat-box mb-3">

<?php foreach ($messages as $msg): ?>
<div class="chat-message">
    <div class="chat-user"><?= htmlspecialchars($msg['username']) ?></div>

    <?php if ($msg['message']): ?>
        <div><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
    <?php endif; ?>

    <?php if ($msg['file_name']): ?>
        <?php
        $fileUrl = "/uploads/chat/".$msg['file_name'];
        ?>

        <?php if (str_starts_with($msg['file_type'], 'image')): ?>
            <a href="<?= $fileUrl ?>" target="_blank">
                <img src="<?= $fileUrl ?>" class="chat-img mt-2">
            </a>

        <?php elseif (str_starts_with($msg['file_type'], 'video')): ?>
            <video src="<?= $fileUrl ?>" controls style="max-width:200px;" class="mt-2"></video>

        <?php else: ?>
            <a href="<?= $fileUrl ?>" download class="btn btn-sm btn-outline-primary mt-2">
                <i class="bi bi-download"></i> Télécharger le fichier
            </a>
        <?php endif; ?>
    <?php endif; ?>

    <div class="chat-time"><?= $msg['created_at'] ?></div>
</div>
<?php endforeach; ?>

</div>

<!-- FORMULAIRE -->
<form method="post" enctype="multipart/form-data" class="card p-3">
    <textarea name="message" class="form-control mb-2" rows="2"
              placeholder="Écrire un message..."></textarea>

    <input type="file" name="file" class="form-control mb-2">

    <button class="btn btn-primary w-100">
        <i class="bi bi-send"></i> Envoyer
    </button>
</form>

</div>

</div>

</body>
</html>
