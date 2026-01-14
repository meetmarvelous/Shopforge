<?php
/**
 * Admin Footer Template
 * 
 * @package ShopForge
 */

defined('SHOPFORGE_INIT') || require_once __DIR__ . '/../../config.php';
?>

    </main>
    
    <!-- Footer -->
    <footer class="py-4 px-6 text-center text-sm text-gray-500">
        &copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.
    </footer>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
// Notification toast
function showNotification(type, message) {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-circle',
        info: 'fa-info-circle'
    };
    
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 max-w-sm ${colors[type]} text-white px-6 py-4 rounded-xl shadow-lg flex items-center flash-message`;
    toast.innerHTML = `
        <i class="fas ${icons[type]} mr-3 text-lg"></i>
        <span>${message}</span>
        <button class="ml-4 text-white/80 hover:text-white" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

// Confirm delete
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Format currency
function formatCurrency(amount) {
    return '<?= CURRENCY_SYMBOL ?>' + parseFloat(amount).toLocaleString();
}
</script>

<?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
