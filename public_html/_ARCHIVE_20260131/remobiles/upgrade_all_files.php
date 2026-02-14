<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Script pour améliorer automatiquement tous les fichiers PHP
 * Transforme les anciens fichiers en utilisant la nouvelle architecture
 */

define('APP_START', true);

// Configuration
$sourceDir = '/mnt/okcomputer/upload';
$targetDir = '/mnt/okcomputer/output';
$backupDir = '/mnt/okcomputer/backup';

// Créer le répertoire de backup
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Liste de tous les fichiers à traiter
$filesToProcess = [
    // Fichiers principaux
    'index.php' => 'improve_index',
    'login.php' => 'improve_login',
    'dashboard.php' => 'improve_dashboard',
    'logout.php' => 'improve_logout',
    
    // Gestion clients
    'clients.php' => 'improve_clients',
    'clients_autocomplete.php' => 'improve_clients_autocomplete',
    'clients_get_remise.php' => 'improve_clients_get_remise',
    
    // Gestion appareils
    'devices_list.php' => 'improve_devices_list',
    'device_register.php' => 'improve_device_register',
    'device_edit.php' => 'improve_device_edit',
    'device_delete.php' => 'improve_device_delete',
    'device_status.php' => 'improve_device_status',
    'device_update_status.php' => 'improve_device_update_status',
    'device_receipt.php' => 'improve_device_receipt',
    
    // Gestion stock
    'stock.php' => 'improve_stock',
    'stock_add.php' => 'improve_stock_add',
    'stock_adjust.php' => 'improve_stock_adjust',
    'stock_import.php' => 'improve_stock_import',
    'stock_audit.php' => 'improve_stock_audit',
    'search_stock.php' => 'improve_search_stock',
    'stock_utils.php' => 'improve_stock_utils',
    'export_articles.php' => 'improve_export_articles',
    
    // Gestion ventes/pos
    'pos_vente.php' => 'improve_pos_vente',
    'pos_vente_panier.php' => 'improve_pos_vente_panier',
    'ventes_historique.php' => 'improve_ventes_historique',
    'delete_vente.php' => 'improve_delete_vente',
    
    // Gestion commandes
    'commandes.php' => 'improve_commandes',
    'edit_commande.php' => 'improve_edit_commande',
    'annuler_commande.php' => 'improve_annuler_commande',
    'delete_commande.php' => 'improve_delete_commande',
    'valider_commande.php' => 'improve_valider_commande',
    'transfer_commande_reparation.php' => 'improve_transfer_commande_reparation',
    'ticket_commande.php' => 'improve_ticket_commande',
    
    // Gestion utilisateurs
    'user_manage.php' => 'improve_user_manage',
    'user_add.php' => 'improve_user_add',
    'user_delete.php' => 'improve_user_delete',
    'create_user.php' => 'improve_create_user',
    
    // Gestion PDF et tickets
    'generate_pdf.php' => 'improve_generate_pdf',
    'generate_invoice.php' => 'improve_generate_invoice',
    'generate_ticket.php' => 'improve_generate_ticket',
    'generate_ticket_sale.php' => 'improve_generate_ticket_sale',
    'invoice_pos.php' => 'improve_invoice_pos',
    'print_epos.php' => 'improve_print_epos',
    'ticket_pos.php' => 'improve_ticket_pos',
    'ticket_vendeur.php' => 'improve_ticket_vendeur',
    'telecharger_ticket.php' => 'improve_telecharger_ticket',
    
    // Gestion acomptes
    'maj_acompte.php' => 'improve_maj_acompte',
    'maj_acompte_commande.php' => 'improve_maj_acompte_commande',
    'maj_acompte_device.php' => 'improve_maj_acompte_device',
    'supprimer_acompte.php' => 'improve_supprimer_acompte',
    'supprimer_acompte_device.php' => 'improve_supprimer_acompte_device',
    'delete_acompte.php' => 'improve_delete_acompte',
    'delete_acompte_commande.php' => 'improve_delete_acompte_commande',
    
    // Divers
    'admin.php' => 'improve_admin',
    'fiche_client.php' => 'improve_fiche_client',
    'api_get_products.php' => 'improve_api_get_products',
    'ajax_get_article.php' => 'improve_ajax_get_article',
    'ajax_search_stock.php' => 'improve_ajax_search_stock',
    'device_part_delete.php' => 'improve_device_part_delete',
    'supprimer.php' => 'improve_supprimer',
    'terminer.php' => 'improve_terminer',
    'audios.php' => 'improve_audios',
    'list_videos.php' => 'improve_list_videos',
    'upload_video.php' => 'improve_upload_video',
    'export_clients.php' => 'improve_export_clients',
    'header.php' => 'improve_header',
    'style.css' => 'improve_style'
];

// Fonctions d'amélioration pour chaque type de fichier
function improve_index($content) {
    return '<?php
/**
 * Calculateur de prix amélioré
 */

require_once \'includes/init.php\';

// Vérifier l\'authentification
$auth->requireLogin();

// Générer le token CSRF
$csrfToken = $auth->generateCSRFToken();

// Définir les variables de page
$pageTitle = "💰 Calculateur de Prix - R.E.Mobiles";
$pageDescription = "Générez vos devis, proformas et factures en quelques secondes";
$currentPage = "calculator";
$showSecondaryNav = true;

include \'includes/header.php\';
?>

<!-- Contenu principal -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Formulaire -->
    <div class="glass p-6 fade-in">
        <h2 class="text-2xl font-bold text-cyan-400 mb-6 flex items-center">
            <i class="bi bi-pencil-square mr-3"></i>
            Formulaire
        </h2>
        
        <form id="priceForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" id="actionInput" value="save">
            
            <!-- Client -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Nom & Prénom du client *
                    </label>
                    <input type="text" id="client_nom" name="clientNom" class="input-modern" placeholder="Tapez le nom du client..." required>
                    <div id="suggestions" class="hidden bg-gray-800 border border-gray-600 rounded-lg mt-1 max-h-40 overflow-y-auto z-10"></div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Téléphone du client *
                    </label>
                    <input type="tel" name="clientTel" class="input-modern" placeholder="Ex : 0694 12 34 56" required>
                </div>
            </div>
            
            <!-- Type et quantité -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Type de document *
                    </label>
                    <select name="docType" class="input-modern" required>
                        <option value="DEVIS">DEVIS</option>
                        <option value="PROFORMA">PROFORMA</option>
                        <option value="FACTURE">FACTURE</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Quantité *
                    </label>
                    <input type="number" name="quantite" class="input-modern" value="1" min="1" required>
                </div>
            </div>
            
            <!-- Pièce -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Nom de la pièce *
                </label>
                <input type="text" name="piece" class="input-modern" placeholder="Ex : Écran iPhone 12" required>
            </div>
            
            <!-- Informations internes -->
            <div class="glass p-4 border border-gray-600 rounded-lg">
                <h3 class="text-lg font-semibold text-cyan-400 mb-3">
                    <i class="bi bi-info-circle mr-2"></i>
                    Informations internes (non imprimées)
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Référence
                        </label>
                        <input type="text" name="refPiece" class="input-modern" placeholder="SKU interne">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Fournisseur
                        </label>
                        <input type="text" name="fournisseur" class="input-modern" placeholder="Ex : PhoneParts EU">
                    </div>
                </div>
            </div>
            
            <!-- Prix -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Prix d\'achat (€) *
                    </label>
                    <input type="number" step="0.01" name="prixAchat" class="input-modern" placeholder="0.00" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Main d\'œuvre (€) *
                    </label>
                    <input type="number" step="0.01" name="mainOeuvre" class="input-modern" placeholder="0.00" required>
                </div>
            </div>
            
            <!-- Boutons d\'action -->
            <div class="flex space-x-4 pt-4">
                <button type="submit" id="btnSave" class="btn btn-primary flex-1">
                    <i class="bi bi-save2"></i>
                    Enregistrer
                </button>
                
                <button type="submit" id="btnPreview" class="btn btn-secondary flex-1">
                    <i class="bi bi-eye"></i>
                    Prévisualiser
                </button>
            </div>
        </form>
    </div>
    
    <!-- Aperçu PDF -->
    <div class="glass p-6 fade-in">
        <h2 class="text-2xl font-bold text-cyan-400 mb-6 flex items-center">
            <i class="bi bi-file-earmark-text mr-3"></i>
            Aperçu du document
        </h2>
        
        <div class="relative">
            <iframe id="ticketFrame" class="w-full h-96 border-2 border-cyan-400 rounded-lg bg-white" style="display: none;"></iframe>
             
            <div id="emptyPreview" class="text-center py-16">
                <i class="bi bi-file-earmark-text text-6xl text-gray-400 mb-4"></i>
                <p class="text-gray-300 text-lg">
                    Remplissez le formulaire puis cliquez sur <strong class="text-cyan-400">Enregistrer</strong> ou <strong class="text-cyan-400">Prévisualiser</strong>
                </p>
            </div>
            
            <div id="previewActions" class="hidden mt-4 flex space-x-4">
                <button id="downloadBtn" class="btn btn-primary">
                    <i class="bi bi-download"></i>
                    Télécharger
                </button>
                
                <button id="printBtn" class="btn btn-secondary">
                    <i class="bi bi-printer"></i>
                    Imprimer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Overlay de chargement -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
    <div class="glass p-8 flex items-center space-x-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-cyan-400"></div>
        <div class="text-white text-lg font-semibold">Génération du PDF...</div>
    </div>
</div>

<script>
document.addEventListener(\'DOMContentLoaded\', function() {
    const form = document.getElementById(\'priceForm\');
    const actionInput = document.getElementById(\'actionInput\');
    const btnSave = document.getElementById(\'btnSave\');
    const btnPreview = document.getElementById(\'btnPreview\');
    const iframe = document.getElementById(\'ticketFrame\');
    const emptyPreview = document.getElementById(\'emptyPreview\');
    const previewActions = document.getElementById(\'previewActions\');
    const loadingOverlay = document.getElementById(\'loadingOverlay\');
    
    // Définir les actions des boutons
    btnSave.addEventListener(\'click\', () => actionInput.value = \'save\');
    btnPreview.addEventListener(\'click\', () => actionInput.value = \'preview\');
    
    // Fonction de chargement
    function setLoading(isLoading) {
        loadingOverlay.style.display = isLoading ? \'flex\' : \'none\';
        btnSave.disabled = isLoading;
        btnPreview.disabled = isLoading;
    }
    
    // Soumission du formulaire
    form.addEventListener(\'submit\', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const action = formData.get(\'action\') || \'save\';
        
        setLoading(true);
        
        fetch(\'generate_pdf.php?action=\' + encodeURIComponent(action), {
            method: \'POST\',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error(\'Erreur PDF\');
            return response.blob();
        })
        .then(blob => {
            const url = URL.createObjectURL(blob);
            iframe.src = url;
            iframe.style.display = \'block\';
            emptyPreview.style.display = \'none\';
            previewActions.classList.remove(\'hidden\');
            
            // Configuration des boutons d\'action
            if (action === \'save\') {
                document.getElementById(\'downloadBtn\').onclick = () => {
                    const a = document.createElement(\'a\');
                    a.href = url;
                    a.download = \'document_\' + new Date().getTime() + \'.pdf\';
                    a.click();
                };
            }
            
            document.getElementById(\'printBtn\').onclick = () => {
                if (iframe && iframe.contentWindow) {
                    iframe.contentWindow.print();
                }
            };
        })
        .catch(err => {
            showNotification(\'Erreur lors de la génération du PDF : \' + err.message, \'error\');
        })
        .finally(() => setLoading(false));
    });
    
    // Autocomplétion des clients
    document.getElementById(\'client_nom\').addEventListener(\'input\', function() {
        const query = this.value.trim();
        const suggestions = document.getElementById(\'suggestions\');
        
        if (query.length < 2) {
            suggestions.classList.add(\'hidden\');
            return;
        }
        
        fetch(\'clients_autocomplete.php?q=\' + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                suggestions.innerHTML = \'\';
                
                if (data.length === 0) {
                    suggestions.classList.add(\'hidden\');
                    return;
                }
                
                data.forEach(client => {
                    const div = document.createElement(\'div\');
                    div.className = \'p-3 hover:bg-gray-700 cursor-pointer text-white\';
                    div.textContent = client.nom + \' (\' + client.telephone + \')\';
                    div.onclick = () => {
                        document.getElementById(\'client_nom\').value = client.nom;
                        const telInput = document.querySelector(\'input[name=\\'clientTel\\']\');
                        if (telInput && client.telephone) {
                            telInput.value = client.telephone;
                        }
                        suggestions.classList.add(\'hidden\');
                    };
                    suggestions.appendChild(div);
                });
                
                suggestions.classList.remove(\'hidden\');
            })
            .catch(() => suggestions.classList.add(\'hidden\'));
    });
    
    // Cacher les suggestions quand on clique ailleurs
    document.addEventListener(\'click\', function(e) {
        if (!e.target.closest(\'#client_nom\') && !e.target.closest(\'#suggestions\')) {
            document.getElementById(\'suggestions\').classList.add(\'hidden\');
        }
    });
});
</script>

<?php include \'includes/footer.php\'; ?>';
}

function improve_login($content) {
    return '<?php
/**
 * Page de connexion sécurisée
 */

require_once \'includes/init.php\';

// Si déjà connecté, rediriger vers le dashboard
if ($auth->isLoggedIn()) {
    header(\'Location: dashboard.php\');
    exit;
}

$error = \'\';

// Traiter la connexion
if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
    $username = trim($_POST[\'username\'] ?? \'\');
    $password = $_POST[\'password\'] ?? \'\';
    
    // Validation basique
    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs";
    } else {
        // Vérifier les tentatives de connexion
        if (!$security->checkLoginAttempts($username)) {
            $error = "Trop de tentatives de connexion. Veuillez réessayer plus tard.";
            $security->logSecurityEvent(\'too_many_login_attempts\', [\'username\' => $username]);
        } else {
            // Tentative de connexion
            if ($auth->login($username, $password)) {
                // Réinitialiser les tentatives
                $security->resetLoginAttempts($username);
                $security->logSecurityEvent(\'successful_login\', [\'username\' => $username]);
                
                // Rediriger vers le dashboard
                header(\'Location: dashboard.php\');
                exit;
            } else {
                // Enregistrer l\'échec
                $security->recordLoginAttempt($username);
                $security->logSecurityEvent(\'failed_login\', [\'username\' => $username]);
                $error = "Nom d\'utilisateur ou mot de passe incorrect";
            }
        }
    }
}

// Générer un token CSRF
$csrfToken = $auth->generateCSRFToken();

$pageTitle = "Connexion - R.E.Mobiles";
$pageDescription = "Connectez-vous à votre espace R.E.Mobiles";

include \'includes/header.php\';
?>

<div class="min-h-screen flex items-center justify-center p-4">
    <div class="glass p-8 w-full max-w-md">
        <!-- Logo et titre -->
        <div class="text-center mb-8">
            <i class="bi bi-cpu-fill text-6xl text-cyan-400 mb-4"></i>
            <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 mb-2">
                R.E.Mobiles
            </h1>
            <p class="text-gray-300">Connectez-vous à votre espace</p>
        </div>
        
        <!-- Message d\'erreur -->
        <?php if ($error): ?>
            <div class="bg-red-500 bg-opacity-20 border border-red-500 border-opacity-30 rounded-lg p-4 mb-6 text-red-200">
                <div class="flex items-center">
                    <i class="bi bi-exclamation-triangle-fill mr-2"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Formulaire de connexion -->
        <form method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <!-- Nom d\'utilisateur -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-300 mb-2">
                    Nom d\'utilisateur
                </label>
                <div class="relative">
                    <input type="text" id="username" name="username" class="input-modern" placeholder="Entrez votre nom d\'utilisateur" required autofocus>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="bi bi-person-fill text-gray-400"></i>
                    </div>
                </div>
            </div>
            
            <!-- Mot de passe -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                    Mot de passe
                </label>
                <div class="relative">
                    <input type="password" id="password" name="password" class="input-modern" placeholder="Entrez votre mot de passe" required>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button type="button" id="togglePassword" class="text-gray-400 hover:text-cyan-400 focus:outline-none">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Bouton de connexion -->
            <button type="submit" class="btn btn-primary w-full">
                <i class="bi bi-unlock-fill mr-2"></i>
                Se connecter
            </button>
        </form>
        
        <!-- Informations -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-400">
                <i class="bi bi-shield-lock mr-1"></i>
                Connexion sécurisée et cryptée
            </p>
        </div>
        
        <!-- Version -->
        <div class="mt-4 text-center">
            <p class="text-xs text-gray-500">
                Version 2.0.0 - R.E.Mobiles © 2024
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener(\'DOMContentLoaded\', function() {
    // Toggle du mot de passe
    const togglePassword = document.getElementById(\'togglePassword\');
    const passwordInput = document.getElementById(\'password\');
    
    togglePassword.addEventListener(\'click\', function() {
        const type = passwordInput.getAttribute(\'type\') === \'password\' ? \'text\' : \'password\';
        passwordInput.setAttribute(\'type\', type);
        
        // Changer l\'icône
        const icon = this.querySelector(\'i\');
        icon.className = type === \'password\' ? \'bi bi-eye-fill\' : \'bi bi-eye-slash-fill\';
    });
    
    // Focus automatique sur le premier champ
    document.getElementById(\'username\').focus();
});
</script>

<?php include \'includes/footer.php\'; ?>';
}

function improve_dashboard($content) {
    return '<?php
/**
 * Tableau de bord principal avec statistiques
 */

require_once \'includes/init.php\';

// Vérifier l\'authentification
$auth->requireLogin();

// Obtenir les statistiques
try {
    $clientModel = new Client();
    $deviceModel = new Device();
    $stockModel = new Stock();
    
    $clientStats = $clientModel->getStats();
    $deviceStats = $deviceModel->getStats();
    $stockStats = $stockModel->getStats();
    $topClients = $clientModel->getTopClients(5);
    $devicesInProgress = $deviceModel->getByStatus(\'En cours\');
    
} catch (Exception $e) {
    logError("Erreur lors de la récupération des statistiques: " . $e->getMessage());
    $clientStats = $deviceStats = $stockStats = [];
    $topClients = $devicesInProgress = [];
}

// Définir les variables de page
$pageTitle = "Tableau de bord - R.E.Mobiles";
$pageDescription = "Gérez votre activité de réparation mobile";
$currentPage = "dashboard";
$showSecondaryNav = true;
$needsCharts = true;

include \'includes/header.php\';
?>

<!-- Statistiques -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in">
    <div class="glass p-6 text-center">
        <div class="text-3xl mb-2">
            <i class="bi bi-people text-blue-400"></i>
        </div>
        <div class="text-2xl font-bold text-white mb-1">
            <?= number_format($clientStats[\'total_clients\'] ?? 0) ?>
        </div>
        <div class="text-gray-400 text-sm">Clients</div>
    </div>
    
    <div class="glass p-6 text-center">
        <div class="text-3xl mb-2">
            <i class="bi bi-tools text-yellow-400"></i>
        </div>
        <div class="text-2xl font-bold text-white mb-1">
            <?= number_format($deviceStats[\'total\'] ?? 0) ?>
        </div>
        <div class="text-gray-400 text-sm">Réparations</div>
    </div>
    
    <div class="glass p-6 text-center">
        <div class="text-3xl mb-2">
            <i class="bi bi-clock-history text-orange-400"></i>
        </div>
        <div class="text-2xl font-bold text-white mb-1">
            <?= number_format($deviceStats[\'en_cours\'] ?? 0) ?>
        </div>
        <div class="text-gray-400 text-sm">En cours</div>
    </div>
    
    <div class="glass p-6 text-center">
        <div class="text-3xl mb-2">
            <i class="bi bi-currency-euro text-green-400"></i>
        </div>
        <div class="text-2xl font-bold text-white mb-1">
            <?= formatPrice($stockStats[\'total_value\'] ?? 0) ?>
        </div>
        <div class="text-gray-400 text-sm">Valeur stock</div>
    </div>
</div>

<!-- Contenu principal -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Réparations en cours -->
    <div class="lg:col-span-2">
        <div class="glass p-6 fade-in">
            <h2 class="text-xl font-bold text-cyan-400 mb-4 flex items-center">
                <i class="bi bi-clock-history mr-2"></i>
                Réparations en cours
            </h2>
            
            <?php if (!empty($devicesInProgress)): ?>
                <div class="space-y-3">
                    <?php foreach (array_slice($devicesInProgress, 0, 5) as $device): ?>
                        <div class="bg-gray-800 rounded-lg p-4 flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-white"><?= htmlspecialchars($device[\'marque\']) ?> <?= htmlspecialchars($device[\'modele\']) ?></div>
                                <div class="text-sm text-gray-400"><?= htmlspecialchars($device[\'client_name\']) ?></div>
                                <div class="text-xs text-gray-500">Ref: <?= htmlspecialchars($device[\'ref\']) ?></div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-yellow-400"><?= htmlspecialchars($device[\'priority\']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($device[\'technician_name\']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($devicesInProgress) > 5): ?>
                    <div class="mt-4 text-center">
                        <a href="devices_list.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-right"></i>
                            Voir toutes les réparations
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-8 text-gray-400">
                    <i class="bi bi-check-circle-fill text-4xl mb-2"></i>
                    <p>Aucune réparation en cours</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Informations rapides -->
    <div class="space-y-6">
        <!-- Top clients -->
        <div class="glass p-6 fade-in">
            <h3 class="text-lg font-bold text-cyan-400 mb-4 flex items-center">
                <i class="bi bi-star-fill mr-2"></i>
                Top clients
            </h3>
            
            <?php if (!empty($topClients)): ?>
                <div class="space-y-2">
                    <?php foreach ($topClients as $client): ?>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-white"><?= htmlspecialchars($client[\'nom\']) ?></div>
                                <div class="text-xs text-gray-400"><?= htmlspecialchars($client[\'telephone\']) ?></div>
                            </div>
                            <div class="text-cyan-400 font-semibold">
                                <?= $client[\'total_devices\'] ?> appareils
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4 text-gray-400">
                    <p>Aucun client encore</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Actions rapides -->
        <div class="glass p-6 fade-in">
            <h3 class="text-lg font-bold text-cyan-400 mb-4 flex items-center">
                <i class="bi bi-lightning-fill mr-2"></i>
                Actions rapides
            </h3>
            
            <div class="space-y-3">
                <a href="device_register.php" class="btn btn-primary w-full">
                    <i class="bi bi-plus-circle"></i>
                    Nouvel appareil
                </a>
                
                <a href="index.php" class="btn btn-secondary w-full">
                    <i class="bi bi-calculator"></i>
                    Calculateur de prix
                </a>
                
                <a href="pos_vente.php" class="btn btn-secondary w-full">
                    <i class="bi bi-cart-check"></i>
                    Point de vente
                </a>
            </div>
        </div>
        
        <!-- Alertes -->
        <div class="glass p-6 fade-in">
            <h3 class="text-lg font-bold text-cyan-400 mb-4 flex items-center">
                <i class="bi bi-exclamation-triangle-fill mr-2"></i>
                Alertes
            </h3>
            
            <div class="space-y-2">
                <?php
                $alerts = [];
                
                // Vérifier les réparations en retard
                try {
                    $overdueDevices = $deviceModel->getOverdueDevices(7);
                    if (!empty($overdueDevices)) {
                        $alerts[] = [
                            'type' => 'warning',
                            'message' => count($overdueDevices) . \' réparations en retard\',
                            'link' => \'devices_list.php\'
                        ];
                    }
                } catch (Exception $e) {
                    // Ignorer les erreurs pour les alertes
                }
                
                // Vérifier le stock faible
                try {
                    $lowStockItems = $stockModel->getLowStockItems();
                    if (!empty($lowStockItems)) {
                        $alerts[] = [
                            'type' => 'warning',
                            'message' => count($lowStockItems) . \' articles avec stock faible\',
                            'link' => \'stock.php\'
                        ];
                    }
                } catch (Exception $e) {
                    // Ignorer les erreurs pour les alertes
                }
                
                if (!empty($alerts)):
                    foreach ($alerts as $alert):
                ?>
                    <div class="flex items-center justify-between p-3 bg-yellow-900 bg-opacity-20 border border-yellow-500 border-opacity-30 rounded">
                        <span class="text-yellow-200 text-sm"><?= htmlspecialchars($alert[\'message\']) ?></span>
                        <a href="<?= $alert[\'link\'] ?>" class="text-yellow-400 hover:text-yellow-300">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                <?php
                    endforeach;
                else:
                ?>
                    <div class="text-center py-4 text-gray-400">
                        <i class="bi bi-check-circle-fill text-2xl mb-2"></i>
                        <p>Tout va bien !</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include \'includes/footer.php\'; ?>';
}

// Fonction principale pour améliorer un fichier
function improveFile($sourcePath, $targetPath, $improveFunction) {
    if (!file_exists($sourcePath)) {
        return false;
    }
    
    // Lire le contenu original
    $originalContent = file_get_contents($sourcePath);
    
    // Sauvegarder l'original
    $backupPath = str_replace('/mnt/okcomputer/upload', '/mnt/okcomputer/backup', $sourcePath);
    $backupDir = dirname($backupPath);
    
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    file_put_contents($backupPath, $originalContent);
    
    // Améliorer le contenu
    $improvedContent = $improveFunction($originalContent);
    
    // Sauvegarder le fichier amélioré
    $targetDir = dirname($targetPath);
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    file_put_contents($targetPath, $improvedContent);
    
    return true;
}

// Traiter tous les fichiers
$results = [];
$totalFiles = count($filesToProcess);
$successCount = 0;

foreach ($filesToProcess as $filename => $improveFunction) {
    $sourcePath = $sourceDir . '/' . $filename;
    $targetPath = $targetDir . '/' . $filename;
    
    echo "Traitement de $filename...\n";
    
    if (improveFile($sourcePath, $targetPath, $improveFunction)) {
        echo "✅ $filename amélioré avec succès\n";
        $successCount++;
        $results[] = ['file' => $filename, 'status' => 'success'];
    } else {
        echo "❌ Erreur lors du traitement de $filename\n";
        $results[] = ['file' => $filename, 'status' => 'error'];
    }
}

// Résumé
echo "\n=== RÉSUMÉ ===\n";
echo "Total de fichiers traités: $totalFiles\n";
echo "Fichiers améliorés avec succès: $successCount\n";
echo "Taux de réussite: " . round(($successCount / $totalFiles) * 100) . "%\n";

// Sauvegarder le rapport
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_files' => $totalFiles,
    'success_count' => $successCount,
    'results' => $results
];

file_put_contents('/mnt/okcomputer/upgrade_report.json', json_encode($report, JSON_PRETTY_PRINT));

echo "\nRapport sauvegardé dans /mnt/okcomputer/upgrade_report.json\n";
?>