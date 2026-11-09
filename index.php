<?php
/**
 * Leo Rent-A-Car – Admin / Manager Login Landing Page
 */
session_start();

// Allow clearing a stale session via ?logout=1
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

// If already authenticated with a valid session, redirect to the operations system
if (!empty($_SESSION['user_id']) && !empty($_SESSION['username'])) {
    header('Location: public/index.php?view=bookings');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        // Connect to the database
        $authenticated = false;
        if (file_exists(__DIR__ . '/config/database.php')) {
            try {
                $dbConfig = require __DIR__ . '/config/database.php';
                $port     = !empty($dbConfig['port']) ? ";port={$dbConfig['port']}" : '';
                $dsn      = "mysql:host={$dbConfig['host']}{$port};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";

                $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT            => 3,
                ]);

                $stmt = $pdo->prepare("SELECT user_id, username, password_hash, role FROM Users WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    $authenticated = true;
                    $_SESSION['user_id']  = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role']     = $user['role'];
                }
            } catch (\Throwable $e) {
                $error = 'Database connection failed. Please try again later.';
            }
        } else {
            $error = 'System configuration not found.';
        }

        if ($authenticated) {
            header('Location: public/index.php?view=bookings');
            exit;
        } elseif ($error === '') {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leo Rent-A-Car | Login</title>

    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="public/assets/css/tailwind.min.css">
    <script src="public/assets/js/tailwind.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            red:       '#DC2626',
                            darkred:   '#991B1B',
                            lightred:  '#FEF2F2',
                            redborder: '#FECACA'
                        }
                    }
                }
            }
        };
    </script>

    <style>
        body, * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }

        /* Subtle animated background gradient */
        .bg-gradient-animate {
            background: linear-gradient(135deg, #f8fafc 0%, #fff1f1 50%, #f8fafc 100%);
            background-size: 200% 200%;
            animation: gradientShift 8s ease infinite;
        }
        @keyframes gradientShift {
            0%   { background-position: 0% 50%;   }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%;   }
        }

        /* Password visibility toggle */
        .toggle-pw { cursor: pointer; }
    </style>
</head>
<body class="bg-gradient-animate min-h-screen flex flex-col items-center justify-center px-4 antialiased selection:bg-red-600 selection:text-white">

    <!-- Card Container -->
    <div class="w-full max-w-sm">

        <!-- Brand Header -->
        <div class="flex flex-col items-center mb-8 text-center">
            <div class="w-20 h-20 rounded-[6px] overflow-hidden border-2 border-red-600 bg-white shadow-sm flex items-center justify-center p-1 mb-4">
                <img src="public/assets/images/Leo-rent-a-car.jpg" alt="Leo Rent-A-Car Logo" class="w-full h-full object-cover rounded-[4px]" onerror="this.onerror=null; this.parentElement.innerHTML='<span class=\'font-black text-red-600 text-2xl\'>LEO</span>';">
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
                LEO <span class="text-red-600">RENT-A-CAR</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1 font-medium">Management Portal &bull; Admin &amp; Manager Access</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-[6px] border border-slate-200 shadow-sm p-8">

            <h2 class="text-base font-bold text-slate-900 mb-1">Sign In</h2>
            <p class="text-xs text-slate-500 mb-6">Enter your credentials to access the operations system.</p>

            <?php if ($error !== ''): ?>
            <div class="flex items-start gap-2.5 bg-red-50 border border-red-200 text-red-700 text-xs font-medium px-4 py-3 rounded-[4px] mb-5">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate class="space-y-5">

                <!-- Username -->
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-700 mb-1.5">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                        required
                        placeholder="Enter your username"
                        class="w-full px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px]
                               placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent
                               transition-all"
                    >
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                            placeholder="Enter your password"
                            class="w-full px-3.5 py-2.5 pr-11 text-sm text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px]
                                   placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent
                                   transition-all"
                        >
                        <!-- Toggle visibility -->
                        <button
                            type="button"
                            class="toggle-pw absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-red-600 transition-colors"
                            onclick="togglePassword()"
                            tabindex="-1"
                            aria-label="Toggle password visibility"
                        >
                            <svg id="eye-open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eye-closed" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7
                                         a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878
                                         l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59
                                         m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0
                                         01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    class="w-full flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800
                           text-white text-sm font-bold py-2.5 rounded-[4px] shadow-sm transition-all
                           focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    Sign In to Operations System
                </button>

            </form>
        </div>

        <!-- Footer note -->
        <p class="text-center text-xs text-slate-400 mt-6">
            &copy; <?= date('Y') ?> <strong class="text-slate-600">Leo Rent-A-Car</strong>.
            Confidential &bull; Authorized Personnel Only.
        </p>

    </div>

    <script>
        function togglePassword() {
            const pwField    = document.getElementById('password');
            const eyeOpen    = document.getElementById('eye-open');
            const eyeClosed  = document.getElementById('eye-closed');
            const isPassword = pwField.type === 'password';
            pwField.type     = isPassword ? 'text' : 'password';
            eyeOpen.classList.toggle('hidden', isPassword);
            eyeClosed.classList.toggle('hidden', !isPassword);
        }

        // Auto-focus username field
        document.getElementById('username').focus();
    </script>

</body>
</html>
