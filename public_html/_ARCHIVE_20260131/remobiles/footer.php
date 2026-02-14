<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

/**
 * Footer commun pour toutes les pages
 */
?>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="text-center">
            <p class="text-gray-400 text-sm">
                &copy; 2024 R.E.Mobiles - Tous droits réservés
            </p>
            <p class="text-gray-500 text-xs mt-2">
                Version 2.0.0 - Système de gestion pour réparations mobiles
            </p>
            <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
            <p class="text-yellow-400 text-xs mt-2">
                ⚠️ Mode débogage activé
            </p>
            <?php endif; ?>
        </div>
    </footer>
    
    <!-- Scripts communs -->
    <script>
        // Animation d'apparition des éléments
        document.addEventListener('DOMContentLoaded', function() {
            // Animer les éléments avec la classe fade-in
            const fadeElements = document.querySelectorAll('.fade-in');
            if (fadeElements.length > 0) {
                anime({
                    targets: fadeElements,
                    opacity: [0, 1],
                    translateY: [30, 0],
                    duration: 800,
                    easing: 'easeOutExpo',
                    delay: anime.stagger(100)
                });
            }
            
            // Gestion des messages flash
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'all 0.3s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
            
            // Gestion des confirmations
            const confirmButtons = document.querySelectorAll('[data-confirm]');
            confirmButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const message = this.getAttribute('data-confirm');
                    if (!confirm(message)) {
                        e.preventDefault();
                    }
                });
            });
            
            // Gestion des tooltips
            const tooltipElements = document.querySelectorAll('[data-tooltip]');
            tooltipElements.forEach(element => {
                element.addEventListener('mouseenter', function() {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'absolute bg-gray-800 text-white text-sm px-2 py-1 rounded shadow-lg z-50';
                    tooltip.textContent = this.getAttribute('data-tooltip');
                    tooltip.style.top = '-30px';
                    tooltip.style.left = '50%';
                    tooltip.style.transform = 'translateX(-50%)';
                    this.style.position = 'relative';
                    this.appendChild(tooltip);
                });
                
                element.addEventListener('mouseleave', function() {
                    const tooltip = this.querySelector('.absolute');
                    if (tooltip) {
                        tooltip.remove();
                    }
                });
            });
        });
        
        // Fonction pour afficher les notifications
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
            } text-white`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Animation d'apparition
            anime({
                targets: notification,
                opacity: [0, 1],
                translateX: [100, 0],
                duration: 300,
                easing: 'easeOutQuad'
            });
            
            // Disparition automatique
            setTimeout(() => {
                anime({
                    targets: notification,
                    opacity: [1, 0],
                    translateX: [0, 100],
                    duration: 300,
                    easing: 'easeInQuad',
                    complete: () => notification.remove()
                });
            }, 3000);
        }
        
        // Fonction pour formater les prix
        function formatPrice(price) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        }
        
        // Fonction pour formater les dates
        function formatDate(date) {
            return new Intl.DateTimeFormat('fr-FR', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            }).format(new Date(date));
        }
    </script>
</body>
</html>