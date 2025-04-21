<?php
require_once '../config/database.php';
require_once '../includes/session.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $agency_type = $_POST['agency_type'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    // $address = $_POST['address'] ?? '';
    // $city = $_POST['city'] ?? '';
    // $state = $_POST['state'] ?? '';
    // $country = $_POST['country'] ?? '';

    // Validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (empty($agency_type)) $errors[] = "Agency type is required";
    if (empty($contact_number)) $errors[] = "Contact number is required";
    // if (empty($address)) $errors[] = "Address is required";
    // if (empty($city)) $errors[] = "City is required";
    // if (empty($state)) $errors[] = "State is required";
    // if (empty($country)) $errors[] = "Country is required";

    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();

        // Check if email already exists
        $checkQuery = "SELECT id FROM agencies WHERE email = :email";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            $errors[] = "Email already registered";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO agencies (name, email, password, agency_type, contact_number) 
                     VALUES (:name, :email, :password, :agency_type, :contact_number)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':agency_type', $agency_type);
            $stmt->bindParam(':contact_number', $contact_number);
            // $stmt->bindParam(':address', $address);
            // $stmt->bindParam(':city', $city);
            // $stmt->bindParam(':state', $state);
            // $stmt->bindParam(':country', $country);

            if ($stmt->execute()) {
                header("Location: login.php?registered=1");
                exit();
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CRCP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Initialize Tailwind with dark mode
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {},
            },
        }
    </script>
    <style>
        body {
            background-image: url('backimg.jpeg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease-out;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #ef4444;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .fade-out {
            opacity: 0;
        }

        /* Animation Classes */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease-out;
        }

        .fade-in.active {
            opacity: 1;
            transform: translateY(0);
        }

        .slide-in-left {
            opacity: 0;
            transform: translateX(-50px);
            transition: all 0.8s ease-out;
        }

        .slide-in-left.active {
            opacity: 1;
            transform: translateX(0);
        }

        .slide-in-right {
            opacity: 0;
            transform: translateX(50px);
            transition: all 0.8s ease-out;
        }

        .slide-in-right.active {
            opacity: 1;
            transform: translateX(0);
        }

        .scale-in {
            opacity: 0;
            transform: scale(0.9);
            transition: all 0.6s ease-out;
        }

        .scale-in.active {
            opacity: 1;
            transform: scale(1);
        }

        /* Register Container Styles */
        .register-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
        }
    </style>
</head>
<body>
    <div id="loading" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 register-container rounded-lg shadow-[0_0_20px_rgba(239,68,68,0.3)] transition-all duration-300 hover:shadow-[0_0_30px_rgba(239,68,68,0.5)] scale-in dark:bg-gray-800 dark:shadow-[0_0_20px_rgba(59,130,246,0.3)] dark:hover:shadow-[0_0_30px_rgba(59,130,246,0.5)]">
            <div class="slide-in-left">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Register</h2>
                    <div class="flex items-center space-x-4">
                        <button id="theme-toggle" class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-5 h-5 text-gray-800 dark:text-white block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-gray-800 dark:text-white hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-300 fade-in" style="transition-delay: 0.2s">
                    Join the Centralized Rescue Coordination Platform
                </p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative fade-in dark:bg-red-900 dark:border-red-700 dark:text-red-100" style="transition-delay: 0.3s" role="alert">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6 fade-in" style="transition-delay: 0.4s" method="POST">
                <div class="rounded-md shadow-sm space-y-3 flex flex-col items-center">
                    <div class="shadow-[0_0_8px_rgba(239,68,68,0.3)] shadow-[0_0_8px_rgba(59,130,246,0.3)] rounded-md w-3/4 dark:shadow-[0_0_8px_rgba(59,130,246,0.5)]">
                        <label for="name" class="sr-only">Agency Name</label>
                        <input id="name" name="name" type="text" required 
                            class="appearance-none relative block w-full px-2.5 py-1.5 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" 
                            placeholder="Agency Name">
                    </div>
                    <div class="shadow-[0_0_8px_rgba(239,68,68,0.3)] shadow-[0_0_8px_rgba(59,130,246,0.3)] rounded-md w-3/4 dark:shadow-[0_0_8px_rgba(59,130,246,0.5)]">
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" required 
                            class="appearance-none relative block w-full px-2.5 py-1.5 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" 
                            placeholder="Email address">
                    </div>
                    <div class="shadow-[0_0_8px_rgba(239,68,68,0.3)] shadow-[0_0_8px_rgba(59,130,246,0.3)] rounded-md w-3/4 dark:shadow-[0_0_8px_rgba(59,130,246,0.5)]">
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required 
                            class="appearance-none relative block w-full px-2.5 py-1.5 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" 
                            placeholder="Password">
                    </div>
                    <div class="shadow-[0_0_8px_rgba(239,68,68,0.3)] shadow-[0_0_8px_rgba(59,130,246,0.3)] rounded-md w-3/4 dark:shadow-[0_0_8px_rgba(59,130,246,0.5)]">
                        <label for="confirm_password" class="sr-only">Confirm Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" required 
                            class="appearance-none relative block w-full px-2.5 py-1.5 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" 
                            placeholder="Confirm Password">
                    </div>
                    <div class="shadow-[0_0_8px_rgba(239,68,68,0.3)] shadow-[0_0_8px_rgba(59,130,246,0.3)] rounded-md w-3/4 dark:shadow-[0_0_8px_rgba(59,130,246,0.5)]">
                        <label for="agency_type" class="sr-only">Agency Type</label>
                        <select id="agency_type" name="agency_type" required 
                            class="appearance-none relative block w-full px-2.5 py-1.5 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500">
                            <option value="">Select Agency Type</option>
                            <option value="rescue">Rescue</option>
                            <option value="medical">Medical</option>
                            <option value="logistics">Logistics</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="shadow-[0_0_8px_rgba(239,68,68,0.3)] shadow-[0_0_8px_rgba(59,130,246,0.3)] rounded-md w-3/4 dark:shadow-[0_0_8px_rgba(59,130,246,0.5)]">
                        <label for="contact_number" class="sr-only">Contact Number</label>
                        <input id="contact_number" name="contact_number" type="tel" required 
                            class="appearance-none relative block w-full px-2.5 py-1.5 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" 
                            placeholder="Contact Number">
                    </div>
                </div>

                <div class="mt-6 flex justify-center">
                    <button type="submit" 
                        class="group relative w-1/3 mx-auto flex justify-center py-2 px-4 border border-transparent text-base font-bold rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-[0_0_10px_rgba(239,68,68,0.5)] shadow-[0_0_10px_rgba(59,130,246,0.5)] hover:shadow-[0_0_15px_rgba(239,68,68,0.7)] hover:shadow-[0_0_15px_rgba(59,130,246,0.7)] transition-all duration-300 transform hover:scale-105">
                        Register
                    </button>
                </div>
            </form>
            <div class="text-center fade-in" style="transition-delay: 0.5s">
                <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Already have an account? Sign in
                </a>
            </div>
        </div>
    </div>

    <script>
        // Theme Toggle Functionality
        const themeToggle = document.getElementById('theme-toggle');
        
        // Function to toggle theme
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
        
        // Set initial theme
        if (localStorage.getItem('theme') === 'dark' || 
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
        
        // Add click event listener
        themeToggle.addEventListener('click', toggleTheme);

        window.addEventListener('load', function() {
            const loadingOverlay = document.getElementById('loading');
            loadingOverlay.classList.add('fade-out');
            
            // Add active class to all animated elements after a short delay
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
                
                // Get all elements with animation classes
                const animatedElements = document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right, .scale-in');
                
                // Add active class to each element with a staggered delay
                animatedElements.forEach((element, index) => {
                    setTimeout(() => {
                        element.classList.add('active');
                    }, index * 100); // 100ms delay between each element
                });
            }, 500);
        });
    </script>
</body>
</html> 