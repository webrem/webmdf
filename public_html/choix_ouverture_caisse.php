<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Ouverture de caisse</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white flex items-center justify-center min-h-screen">

<div class="bg-slate-800 p-6 rounded-xl shadow-xl w-full max-w-md text-center">

    <h1 class="text-xl font-bold mb-3 text-green-400">
        💰 Ouverture de caisse
    </h1>

    <p class="text-gray-300 mb-6">
        Aucune caisse n’est ouverte pour aujourd’hui.<br>
        Souhaitez-vous ouvrir la caisse maintenant ?
    </p>

    <div class="space-y-3">
        <a href="ouverture_caisse.php"
           class="block bg-green-600 hover:bg-green-700 p-3 rounded font-semibold">
            ✅ Ouvrir la caisse
        </a>

        <a href="dashboard.php?skip_caisse=1"
           class="block bg-slate-600 hover:bg-slate-700 p-3 rounded font-semibold">
            ➡️ Ne pas ouvrir (continuer)
        </a>
    </div>

    <p class="text-xs text-gray-400 mt-4">
        ⚠️ Les ventes seront bloquées tant que la caisse n’est pas ouverte.
    </p>

</div>

</body>
</html>
