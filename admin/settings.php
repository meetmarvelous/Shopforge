<?php
/**
 * Admin Settings Page
 * 
 * Configure system settings.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/../includes/functions.php';

require_admin();
require_role('admin'); // Only admins can access settings

$db = db();

// Get current settings
$settings = [];
$settingsResult = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
foreach ($settingsResult as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $settingsToUpdate = [
        'site_name' => Security::sanitizeString($_POST['site_name'] ?? ''),
        'site_tagline' => Security::sanitizeString($_POST['site_tagline'] ?? ''),
        'contact_email' => Security::sanitizeEmail($_POST['contact_email'] ?? ''),
        'contact_phone' => Security::sanitizeString($_POST['contact_phone'] ?? ''),
        'contact_address' => Security::sanitizeString($_POST['contact_address'] ?? ''),
        'currency_symbol' => Security::sanitizeString($_POST['currency_symbol'] ?? '₦'),
        'tax_rate' => (float)($_POST['tax_rate'] ?? 0),
        'free_shipping_threshold' => (float)($_POST['free_shipping_threshold'] ?? 0),
        'default_shipping_cost' => (float)($_POST['default_shipping_cost'] ?? 0),
        'social_facebook' => Security::sanitizeUrl($_POST['social_facebook'] ?? ''),
        'social_instagram' => Security::sanitizeUrl($_POST['social_instagram'] ?? ''),
        'social_twitter' => Security::sanitizeUrl($_POST['social_twitter'] ?? ''),
    ];
    
    foreach ($settingsToUpdate as $key => $value) {
        $exists = $db->exists('settings', 'setting_key = ?', [$key]);
        if ($exists) {
            $db->update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
        } else {
            $db->insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
        }
        $settings[$key] = $value;
    }
    
    flash_success('Settings saved successfully.');
    redirect(url('admin/settings.php'));
}

$pageTitle = 'Settings';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="lg:ml-64 min-h-screen flex flex-col">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>
    
    <main class="flex-1 p-6">
        <div class="max-w-3xl">
            <form method="POST" action="" class="space-y-6">
                <?= csrf_field() ?>
                
                <!-- General Settings -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">General Settings</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Site Name</label>
                            <input type="text" name="site_name" value="<?= e($settings['site_name'] ?? SITE_NAME) ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tagline</label>
                            <input type="text" name="site_tagline" value="<?= e($settings['site_tagline'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                    </div>
                </div>
                
                <!-- Contact Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="contact_email" value="<?= e($settings['contact_email'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="text" name="contact_phone" value="<?= e($settings['contact_phone'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                            <textarea name="contact_address" rows="2"
                                      class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none resize-none"><?= e($settings['contact_address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Store Settings -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Store Settings</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Currency Symbol</label>
                            <input type="text" name="currency_symbol" value="<?= e($settings['currency_symbol'] ?? CURRENCY_SYMBOL) ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tax Rate (%)</label>
                            <input type="number" name="tax_rate" value="<?= e($settings['tax_rate'] ?? TAX_RATE) ?>" step="0.01" min="0"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Free Shipping Threshold</label>
                            <input type="number" name="free_shipping_threshold" value="<?= e($settings['free_shipping_threshold'] ?? FREE_SHIPPING_THRESHOLD) ?>" step="0.01" min="0"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Default Shipping Cost</label>
                            <input type="number" name="default_shipping_cost" value="<?= e($settings['default_shipping_cost'] ?? DEFAULT_SHIPPING_COST) ?>" step="0.01" min="0"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                    </div>
                </div>
                
                <!-- Social Links -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Social Media Links</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fab fa-facebook text-blue-600 mr-2"></i>Facebook
                            </label>
                            <input type="url" name="social_facebook" value="<?= e($settings['social_facebook'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none"
                                   placeholder="https://facebook.com/yourpage">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fab fa-instagram text-pink-600 mr-2"></i>Instagram
                            </label>
                            <input type="url" name="social_instagram" value="<?= e($settings['social_instagram'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none"
                                   placeholder="https://instagram.com/yourpage">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fab fa-twitter text-sky-500 mr-2"></i>Twitter
                            </label>
                            <input type="url" name="social_twitter" value="<?= e($settings['social_twitter'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none"
                                   placeholder="https://twitter.com/yourpage">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">
                        <i class="fas fa-save mr-2"></i>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </main>
    
<?php require_once __DIR__ . '/includes/footer.php'; ?>
