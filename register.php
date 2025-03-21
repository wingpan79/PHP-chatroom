<?php
define('IN_CHAT', true);
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $nickname = trim($_POST['nickname']);
        
        if (empty($username) || empty($password) || empty($nickname)) {
            throw new Exception("All fields must be filled");
        }
        
        // Check if IP is banned
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $pdo->prepare("SELECT * FROM ip_blacklist WHERE ip = ?");
        $stmt->execute([$ip]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("This IP has been banned, cannot register. Contact administrator for issues");
        }
        
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username already exists");
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Create new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, nickname, ip) VALUES (?, ?, ?, ?)");
        if (!$stmt->execute([$username, $hashed_password, $nickname, $ip])) {
            throw new Exception("Failed to create user");
        }
        
        // Get new user ID
        $user_id = $pdo->lastInsertId();
        if (!$user_id) {
            throw new Exception("Failed to get user ID");
        }
        
        // Initialize user location record
        $stmt = $pdo->prepare("INSERT INTO user_locations (user_id, city) VALUES (?, 'Unknown')");
        if (!$stmt->execute([$user_id])) {
            throw new Exception("Failed to create location record");
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Registration successful, redirect to login page
        header("Location: login.php?msg=" . urlencode("Registration successful, please login"));
        exit;
        
    } catch (Exception $e) {
        // If transaction is started, roll back
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Record error log
        error_log("Registration error: " . $e->getMessage());
        
        // Display error message
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Online Chat Room</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #a1c4fd 0%, #c2e9fb 100%);
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease;
        }
        .card-header {
            background: #fff;
            border-bottom: 2px solid #f8f9fa;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 2px solid #eee;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #a1c4fd;
            box-shadow: 0 0 0 0.2rem rgba(161,196,253,0.25);
        }
        .btn-primary {
            background: linear-gradient(45deg, #a1c4fd, #c2e9fb);
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(161,196,253,0.4);
        }
        .btn-link {
            color: #666;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-link:hover {
            color: #333;
            text-decoration: underline;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .input-group-text {
            background: transparent;
            border: 2px solid #eee;
            border-right: none;
        }
        .input-group .form-control {
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header text-center">
                        <i class="fas fa-user-plus fa-2x mb-3"></i>
                        <h4 class="mb-0">User Registration</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-smile"></i>
                                    </span>
                                    <input type="text" name="nickname" class="form-control" placeholder="Enter nickname" required>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Register
                                </button>
                            </div>
                            <div class="text-center mt-4">
                                <a href="login.php" class="btn btn-link">
                                    <i class="fas fa-sign-in-alt me-1"></i>Have an account? Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 