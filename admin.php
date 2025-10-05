<?php
session_start();

// Load admin configuration
$admin_config = require_once __DIR__ . '/config/admin_config.php';

// Function to verify password
function verify_admin_password($password) {
    global $admin_config;
    return password_verify($password, $admin_config['admin_password_hash']);
}

// Handle login
if ($_POST['action'] === 'login' && isset($_POST['password'])) {
    if (verify_admin_password($_POST['password'])) {
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_login_time'] = time();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = "Invalid password. Please try again.";
    }
}

// Handle logout
if ($_GET['action'] === 'logout') {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Check session timeout
if (isset($_SESSION['admin_authenticated']) && isset($_SESSION['admin_login_time'])) {
    if (time() - $_SESSION['admin_login_time'] > $admin_config['session_timeout']) {
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    // Update last activity time
    $_SESSION['admin_login_time'] = time();
}

// Check if user is authenticated
$is_authenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;

// Handle form submissions
$show_form = '';
if (isset($_GET['type'])) {
    $show_form = $_GET['type'];
}

// If not authenticated, show login form
if (!$is_authenticated) {
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CASICAM - Admin Login</title>
    <link rel="icon" type="image/png" href="./assets/images/section1_logo.svg">
    <link href="./dist/styles.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Saira:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        html {scroll-behavior: smooth;}
        * {margin: 0; padding: 0; box-sizing: border-box;}
        html, body {width: 100%; overflow-x: hidden;}
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Enhanced input styling */
        .admin-input {
            background-color: #1f2937 !important;
            color: #ffffff !important;
            border: 1px solid #4b5563;
            transition: all 0.2s ease;
        }
        
        .admin-input:focus {
            background-color: #111827 !important;
            color: #ffffff !important;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
            outline: none;
        }
        
        .admin-input:hover {
            border-color: #6b7280;
        }
        
        .admin-input::placeholder {
            color: #9ca3af !important;
        }
        
        /* Ensure text visibility */
        input[type="password"] {
            background-color: #1f2937 !important;
            color: #ffffff !important;
        }
        
        /* Special styling for select dropdowns - force black text on white background */
        select.admin-input {
            background-color: #ffffff !important;
            color: #000000 !important;
            border: 1px solid #4b5563 !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
        }
        
        select.admin-input option {
            background-color: #ffffff !important;
            color: #000000 !important;
        }
        
        /* Force visibility for all select elements */
        select {
            background-color: #ffffff !important;
            color: #000000 !important;
        }
        
        select option {
            background-color: #ffffff !important;
            color: #000000 !important;
        }
        
        /* Ultra-specific targeting for certificate type dropdown */
        #cert_type {
            background: #ffffff !important;
            color: #000000 !important;
            border: 1px solid #666666 !important;
        }
        
        #cert_type option {
            background: #ffffff !important;
            color: #000000 !important;
            padding: 8px !important;
        }
        
        /* Override any Tailwind or other framework styles */
        select[name="cert_type"] {
            background: white !important;
            color: black !important;
        }
        
        select[name="cert_type"] option {
            background: white !important;
            color: black !important;
        }
    </style>
</head>
<body class="bg-[#0a0a0a] text-white min-h-screen font-saira flex items-center justify-center">
    <div class="glass rounded-lg p-8 w-96 max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">CASICAM</h1>
            <p class="text-gray-300">Admin Panel Access</p>
        </div>
        
        <?php if (isset($login_error)): ?>
            <div class="bg-red-500/20 border border-red-500/50 rounded p-3 mb-4">
                <p class="text-red-200 text-sm"><?php echo htmlspecialchars($login_error); ?></p>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="space-y-6">
            <input type="hidden" name="action" value="login">
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                    Admin Password
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    class="admin-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter admin password"
                    autocomplete="current-password"
                >
            </div>
            
            <button 
                type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-800"
            >
                Login to Admin Panel
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <a href="index.php" class="text-gray-400 hover:text-white text-sm transition duration-200">
                ← Back to main site
            </a>
        </div>
    </div>
    
    <script>
        // Auto-focus password field
        document.getElementById('password').focus();
        
        // Clear any stored form data on page load for security
        window.addEventListener('load', function() {
            document.getElementById('password').value = '';
        });
    </script>
</body>
</html>
<?php
    exit; // Stop execution here if not authenticated
}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="./dist/styles.css" rel="stylesheet">
        <title>CASICAM - Admin Panel</title>
        <!-- Favicon -->
        <link rel="icon" type="image/png" href="./assets/images/section1_logo.svg">
        <!-- Smooth Scrolling -->
        <style>
            html {scroll-behavior: smooth;}
            * {margin: 0; padding: 0; box-sizing: border-box;}
            html, body {width: 100%; overflow-x: hidden;}
            /* Glass morphism effect */
            .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            }
            /* Card hover effects */
            .card-hover {
            transition: all 0.3s ease;
            border: .5px solid rgba(229, 231, 235, 0.1);
            }
            .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 40px 80px rgba(0, 0, 0, 1);
            }
        </style>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Saira:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    </head>
    <body id="top" class="bg-[#0a0a0a] text-white min-h-screen font-saira">
        <!-- Admin Header -->
        <header class="bg-gray-900/80 backdrop-blur-sm border-b border-gray-700/50 sticky top-0 z-50">
            <div class="container mx-auto px-4 py-3 flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <h1 class="text-xl font-bold">CASICAM Admin Panel</h1>
                    <span class="bg-green-500/20 text-green-400 px-2 py-1 rounded text-xs">Authenticated</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-400 text-sm">
                        Session: <?php echo date('H:i:s', $_SESSION['admin_login_time']); ?>
                    </span>
                    <a 
                        href="?action=logout" 
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm transition duration-200"
                        onclick="return confirm('Are you sure you want to logout?')"
                    >
                        Logout
                    </a>
                </div>
            </div>
        </header>

        <!-- Admin Content Area -->
        <main class="container mx-auto px-4 py-8">
            <?php if ($show_form === 'certificate'): ?>
                <!-- Certificate Form -->
                <div class="max-w-4xl mx-auto">
                    <div class="glass rounded-lg p-8">
                        <div class="flex items-center mb-6">
                            <a href="admin.php" class="text-gray-400 hover:text-white mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </a>
                            <h2 class="text-2xl font-bold text-white">Create Certificate</h2>
                        </div>
                        
                        <!-- Certificate Type Selection -->
                        <div class="mb-8">
                            <label for="cert_type" class="block text-sm font-medium text-gray-300 mb-2">
                                Certificate Type *
                            </label>
                            <select 
                                id="cert_type" 
                                name="cert_type" 
                                required
                                class="admin-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-blue-500"
                                style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #666666 !important;"
                                onchange="toggleCustomCertificateName()"
                            >
                                <option value="">Select certificate type</option>
                                <option value="participation">Participation</option>
                                <option value="presentation">Presentation</option>
                                <option value="honor">Honor</option>
                                <option value="organization">Organization</option>
                                <option value="others">Others</option>
                            </select>
                        </div>

                        <!-- Custom Certificate Name (shown when "others" is selected) -->
                        <div id="custom-cert-name" class="mb-8" style="display: none;">
                            <label for="custom_cert_type" class="block text-sm font-medium text-gray-300 mb-2">
                                Custom Certificate Name
                            </label>
                            <input 
                                type="text" 
                                id="custom_cert_type" 
                                name="custom_cert_type" 
                                class="admin-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter custom certificate name (e.g., 'Excellence', 'Achievement')"
                                style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #666666 !important;"
                            >
                        </div>

                        <!-- Certificate Generation Mode Toggle -->
                        <div class="mb-8">
                            <div class="flex space-x-4">
                                <button 
                                    type="button" 
                                    id="single-mode-btn"
                                    onclick="toggleMode('single')"
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200"
                                >
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Single Person
                                </button>
                                <button 
                                    type="button" 
                                    id="bulk-mode-btn"
                                    onclick="toggleMode('bulk')"
                                    class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200"
                                >
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Excel File
                                </button>
                            </div>
                        </div>

                        <!-- Single Person Form -->
                        <div id="single-form" class="certificate-mode">
                            <form action="certificate.php" method="POST" class="space-y-6" onsubmit="return validateCertificateForm()">
                                <input type="hidden" name="cert_type" id="single_cert_type">
                                <input type="hidden" name="custom_cert_type" id="single_custom_cert_type">
                                <input type="hidden" name="mode" value="single">
                                
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-300 mb-2">
                                        Full Name *
                                    </label>
                                    <input 
                                        type="text" 
                                        id="full_name" 
                                        name="full_name" 
                                        required
                                        class="admin-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-blue-500"
                                        placeholder="Enter full name"
                                        style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #666666 !important;"
                                    >
                                </div>
                                
                                <div>
                                    <label for="organization" class="block text-sm font-medium text-gray-300 mb-2">
                                        Organization *
                                    </label>
                                    <input 
                                        type="text" 
                                        id="organization" 
                                        name="organization" 
                                        required
                                        class="admin-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-blue-500"
                                        placeholder="Enter organization name"
                                        style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #666666 !important;"
                                    >
                                </div>
                                
                                <!-- Email field for single mode -->
                                <div class="mb-4">
                                    <label class="block text-gray-300 text-sm font-medium mb-2" for="recipient_email">
                                        Recipient Email (optional)
                                    </label>
                                    <input 
                                        type="email" 
                                        id="recipient_email" 
                                        name="recipient_email" 
                                        class="admin-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-blue-500"
                                        placeholder="Enter recipient email address"
                                        style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #666666 !important;"
                                    >
                                </div>
                                
                                <div class="flex gap-3 pt-4">
                                    <button 
                                        type="submit"
                                        name="action"
                                        value="email_certificate"
                                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200"
                                    >
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        Email
                                    </button>
                                    <button 
                                        type="submit"
                                        name="action"
                                        value="download_certificate"
                                        class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200"
                                    >
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Download
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Bulk Upload Form -->
                        <div id="bulk-form" class="certificate-mode" style="display: none;">
                            <form action="certificate.php" method="POST" enctype="multipart/form-data" class="space-y-6" onsubmit="return validateCertificateForm()">
                                <input type="hidden" name="cert_type" id="bulk_cert_type">
                                <input type="hidden" name="custom_cert_type" id="bulk_custom_cert_type">
                                <input type="hidden" name="mode" value="bulk">
                                
                                <div>
                                    <label for="excel_file" class="block text-sm font-medium text-gray-300 mb-2">
                                        Excel File (Names and Organizations)
                                    </label>
                                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center">
                                        <input 
                                            type="file" 
                                            id="excel_file" 
                                            name="excel_file" 
                                            accept=".xlsx,.xls,.csv"
                                            required
                                            class="hidden"
                                            onchange="handleFileSelect(this)"
                                        >
                                        <div id="file-drop-area" class="cursor-pointer" onclick="document.getElementById('excel_file').click()">
                                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                            </svg>
                                            <p class="text-gray-300 mb-2">
                                                <span class="font-medium">Click to upload</span> or drag and drop
                                            </p>
                                            <p class="text-gray-500 text-sm">Excel files (.xlsx, .xls, .csv)</p>
                                            <div id="file-info" class="mt-4 text-green-400 hidden"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex gap-3 pt-4">
                                    <button 
                                        type="submit"
                                        name="action"
                                        value="email_certificates"
                                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200"
                                    >
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        Email All
                                    </button>
                                    <button 
                                        type="submit"
                                        name="action"
                                        value="download_certificates"
                                        class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200"
                                    >
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M7 13l3 3 7-7"></path>
                                        </svg>
                                        Download ZIP
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <script>
                    let currentMode = 'single';
                    
                    // Validation function to ensure certificate type is selected
                    function validateCertificateForm() {
                        const certType = document.getElementById('cert_type').value;
                        const customCertType = document.getElementById('custom_cert_type').value;
                        
                        if (!certType) {
                            alert('Please select a certificate type before proceeding.');
                            document.getElementById('cert_type').focus();
                            return false;
                        }
                        
                        if (certType === 'others' && !customCertType.trim()) {
                            alert('Please enter a custom certificate name when "Others" is selected.');
                            document.getElementById('custom_cert_type').focus();
                            return false;
                        }
                        
                        return true;
                    }
                    
                    function toggleMode(mode) {
                        currentMode = mode;
                        
                        // Update button styles
                        const singleBtn = document.getElementById('single-mode-btn');
                        const bulkBtn = document.getElementById('bulk-mode-btn');
                        
                        if (mode === 'single') {
                            singleBtn.className = 'flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200';
                            bulkBtn.className = 'flex-1 bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200';
                            document.getElementById('single-form').style.display = 'block';
                            document.getElementById('bulk-form').style.display = 'none';
                        } else {
                            singleBtn.className = 'flex-1 bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200';
                            bulkBtn.className = 'flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200';
                            document.getElementById('single-form').style.display = 'none';
                            document.getElementById('bulk-form').style.display = 'block';
                        }
                        
                        // Update hidden cert_type fields
                        updateCertType();
                    }
                    
                    function updateCertType() {
                        const certType = document.getElementById('cert_type').value;
                        document.getElementById('single_cert_type').value = certType;
                        document.getElementById('bulk_cert_type').value = certType;
                        
                        // Also update custom cert type fields
                        updateCustomCertType();
                    }
                    
                    function toggleCustomCertificateName() {
                        const certType = document.getElementById('cert_type').value;
                        const customField = document.getElementById('custom-cert-name');
                        const customInput = document.getElementById('custom_cert_type');
                        
                        if (certType === 'others') {
                            customField.style.display = 'block';
                            customInput.required = true;
                        } else {
                            customField.style.display = 'none';
                            customInput.required = false;
                            customInput.value = '';
                        }
                        
                        updateCustomCertType();
                    }
                    
                    function updateCustomCertType() {
                        const customCertType = document.getElementById('custom_cert_type').value;
                        document.getElementById('single_custom_cert_type').value = customCertType;
                        document.getElementById('bulk_custom_cert_type').value = customCertType;
                    }
                    
                    // Update custom cert type when input changes
                    document.getElementById('custom_cert_type').addEventListener('input', updateCustomCertType);
                    
                    // Update cert type when main dropdown changes
                    document.getElementById('cert_type').addEventListener('change', updateCertType);
                    
                    function handleFileSelect(input) {
                        const file = input.files[0];
                        const fileInfo = document.getElementById('file-info');
                        
                        if (file) {
                            fileInfo.textContent = `Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                            fileInfo.classList.remove('hidden');
                        } else {
                            fileInfo.classList.add('hidden');
                        }
                    }
                    
                    // Drag and drop functionality
                    const dropArea = document.getElementById('file-drop-area');
                    
                    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                        dropArea.addEventListener(eventName, preventDefaults, false);
                    });
                    
                    function preventDefaults(e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    
                    ['dragenter', 'dragover'].forEach(eventName => {
                        dropArea.addEventListener(eventName, highlight, false);
                    });
                    
                    ['dragleave', 'drop'].forEach(eventName => {
                        dropArea.addEventListener(eventName, unhighlight, false);
                    });
                    
                    function highlight(e) {
                        dropArea.classList.add('border-blue-500', 'bg-blue-50');
                    }
                    
                    function unhighlight(e) {
                        dropArea.classList.remove('border-blue-500', 'bg-blue-50');
                    }
                    
                    dropArea.addEventListener('drop', handleDrop, false);
                    
                    function handleDrop(e) {
                        const dt = e.dataTransfer;
                        const files = dt.files;
                        
                        if (files.length > 0) {
                            document.getElementById('excel_file').files = files;
                            handleFileSelect(document.getElementById('excel_file'));
                        }
                    }
                </script>
                
            <?php elseif ($show_form === 'invoice'): ?>
                <!-- Invoice Form -->
                <div class="max-w-2xl mx-auto">
                    <div class="glass rounded-lg p-8">
                        <div class="flex items-center mb-6">
                            <a href="admin.php" class="text-gray-400 hover:text-white mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </a>
                            <h2 class="text-2xl font-bold text-white">Create Invoice</h2>
                        </div>
                        
                        <form action="invoice.php" method="POST" class="space-y-6">
                            <div>
                                <label for="invoice_full_name" class="block text-sm font-medium text-gray-300 mb-2">
                                    Full Name *
                                </label>
                                <input 
                                    type="text" 
                                    id="invoice_full_name" 
                                    name="full_name" 
                                    required
                                    class="admin-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-green-500"
                                    placeholder="Enter full name"
                                    style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #666666 !important;"
                                >
                            </div>
                            
                            <div>
                                <label for="invoice_organization" class="block text-sm font-medium text-gray-300 mb-2">
                                    Organization *
                                </label>
                                <input 
                                    type="text" 
                                    id="invoice_organization" 
                                    name="organization" 
                                    required
                                    class="admin-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-green-500"
                                    placeholder="Enter organization name"
                                    style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #666666 !important;"
                                >
                            </div>
                            
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-300 mb-2">
                                    Amount & Currency *
                                </label>
                                <div class="flex gap-3">
                                    <input 
                                        type="number" 
                                        id="amount" 
                                        name="amount" 
                                        step="0.01"
                                        min="0"
                                        required
                                        class="admin-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-green-500"
                                        placeholder="Enter amount (e.g., 1500.00)"
                                        style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #666666 !important;"
                                    >
                                    <select
                                        id="currency"
                                        name="currency"
                                        class="admin-input px-4 py-3 rounded-lg focus:ring-2 focus:ring-green-500"
                                        style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #666666 !important; min-width: 100px;"
                                        required
                                    >
                                        <option value="MAD" selected>MAD</option>
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Email field for invoice -->
                            <div class="mb-4">
                                <label class="block text-gray-300 text-sm font-medium mb-2" for="invoice_recipient_email">
                                    Recipient Email (optional)
                                </label>
                                <input 
                                    type="email" 
                                    id="invoice_recipient_email" 
                                    name="recipient_email" 
                                    class="admin-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-green-500"
                                    placeholder="Enter recipient email address"
                                    style="background-color: #ffffff !important; color: #000000 !important; border: 1px solid #666666 !important;"
                                >
                            </div>
                            
                            <div class="flex gap-3 pt-4">
                                <button 
                                    type="submit"
                                    name="action"
                                    value="email_invoice"
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200"
                                >
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    Email
                                </button>
                                <button 
                                    type="submit"
                                    name="action"
                                    value="download_invoice"
                                    class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200"
                                >
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Download
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Main Selection -->
                <div class="glass rounded-lg p-6 mb-6">
                    <h2 class="text-2xl font-bold mb-4">Welcome to Admin Panel</h2>
                    <p class="text-gray-300">Choose what you would like to create:</p>
                </div>
                
                <!-- Main Options -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                    <div class="glass rounded-lg p-8 card-hover cursor-pointer" onclick="createCertificate()">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold mb-3 text-white">Create Certificate</h3>
                            <p class="text-gray-400 text-sm mb-4">Generate a professional certificate for participants</p>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                                Create Certificate
                            </button>
                        </div>
                    </div>
                    
                    <div class="glass rounded-lg p-8 card-hover cursor-pointer" onclick="createInvoice()">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold mb-3 text-white">Create Invoice</h3>
                            <p class="text-gray-400 text-sm mb-4">Generate a professional invoice for services</p>
                            <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition duration-200">
                                Create Invoice
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
        
        <script>
            function createCertificate() {
                window.location.href = 'admin.php?type=certificate';
            }
            
            function createInvoice() {
                window.location.href = 'admin.php?type=invoice';
            }
        </script>
    </body>
</html>