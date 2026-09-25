<?php
$page = 'settings';

global $conn;

// Get platform settings (only one row in this table)
$stmt = $conn->query("SELECT * FROM platform_settings LIMIT 1");
$settings = $stmt->fetch();

// If no settings exist, create defaults
if (!$settings) {
    $settings = [
        'whatsapp' => '',
        'details' => '{}'
    ];
}

$platformWhatsapp = $settings['whatsapp'] ?? '';

ob_start();
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Platform Settings</h2>
    <p class="text-gray-500 mt-1">Configure your platform's global settings</p>
</div>

<form id="settingsForm" onsubmit="saveSettings(event)" class="space-y-6">

    <!-- WhatsApp Settings -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center mr-3">
                <i class="fab fa-whatsapp text-green-600 text-xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900">WhatsApp Payment Settings</h3>
                <p class="text-sm text-gray-500">Configure where you'll receive payment notifications</p>
            </div>
        </div>
        
        <div class="max-w-2xl">
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-yellow-600 text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Important:</strong> This WhatsApp number will receive all payment notifications and order alerts from your platform. Make sure this number is active and connected to WhatsApp.
                        </p>
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    WhatsApp Number for Payments *
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fab fa-whatsapp text-green-500 text-lg"></i>
                    </div>
                    <input type="text" name="whatsapp" required 
                           value="<?= htmlspecialchars($platformWhatsapp) ?>"
                           class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent text-lg"
                           placeholder="+91 9876543210">
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Enter the WhatsApp number with country code where you want to receive payment confirmations
                </p>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="submit" 
                class="px-8 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium shadow-lg flex items-center">
            <i class="fas fa-save mr-2"></i> Save Settings
        </button>
    </div>
</form>

<script>
function saveSettings(e) {
    e.preventDefault();
    
    const whatsapp = $('input[name="whatsapp"]').val().trim();
    
    if (!whatsapp) {
        alert('Please enter a WhatsApp number');
        return;
    }
    
    const submitBtn = $(e.target).find('button[type="submit"]');
    const originalHtml = submitBtn.html();
    
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Saving...');
    
    $.ajax({
        url: '<?= url("/api/owner/settings?action=save") ?>',
        method: 'POST',
        data: { whatsapp: whatsapp },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show success message
                const toast = $('<div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-xl shadow-lg z-50 flex items-center"><i class="fas fa-check-circle mr-2"></i>Settings saved successfully!</div>');
                $('body').append(toast);
                setTimeout(() => toast.fadeOut(300, () => toast.remove()), 3000);
            } else {
                alert('Error: ' + (response.error || 'Unknown error'));
            }
            submitBtn.prop('disabled', false).html(originalHtml);
        },
        error: function(xhr) {
            let errorMsg = 'Error saving settings. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMsg = xhr.responseJSON.error;
            }
            alert(errorMsg);
            submitBtn.prop('disabled', false).html(originalHtml);
        }
    });
}
</script>

<?php
$content = ob_get_clean();
require 'views/owner/layout.php';
?>