<?php
/**
 * Admin Header Template
 * 
 * Head section for admin panel.
 * 
 * @package ShopForge
 */

defined('SHOPFORGE_INIT') || require_once __DIR__ . '/../../config.php';

// Page title
$pageTitle = isset($pageTitle) ? $pageTitle . ' - Admin | ' . SITE_NAME : 'Admin | ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="robots" content="noindex, nofollow">
    
    <title><?= e($pageTitle) ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        },
                        sidebar: {
                            bg: '#1e1b4b',
                            hover: '#312e81',
                            active: '#4338ca'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Admin Styles -->
    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
        }
        
        .sidebar-link {
            @apply flex items-center px-4 py-3 rounded-xl text-white/70 hover:text-white hover:bg-sidebar-hover transition-all duration-200;
        }
        
        .sidebar-link.active {
            @apply text-white bg-sidebar-active;
        }
        
        .sidebar-link i {
            @apply w-5 text-center mr-3;
        }
        
        .stat-card {
            @apply bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all duration-300;
        }
        
        .stat-card:hover {
            @apply shadow-lg transform -translate-y-1;
        }
        
        .data-table th {
            @apply px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50;
        }
        
        .data-table td {
            @apply px-4 py-4 text-sm text-gray-700 border-b border-gray-100;
        }
        
        .data-table tr:hover td {
            @apply bg-gray-50;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        /* Flash messages */
        .flash-message {
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="bg-gray-100 min-h-screen">
