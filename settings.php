<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

include 'db.php';

// Ensure the 'uploads' directory exists
if (!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
}

// Fetch user details
$username = $_SESSION['user'];
$user_query = "SELECT * FROM users WHERE username = '$username'";
$user_result = $conn->query($user_query);
if ($user_result && $user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $subscription_start = isset($user['subscription_start']) ? new DateTime($user['subscription_start']) : null;
    $current_date = new DateTime();
    $subscription_duration = $subscription_start ? $subscription_start->diff($current_date)->format('%y years, %m months, %d days') : "Not available";
} else {
    die("User not found.");
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_username = $conn->real_escape_string($_POST['username']);
    $profile_picture = $_FILES['profile_picture'];

    // Handle profile picture upload
    if ($profile_picture['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($profile_picture['name']);
        if (move_uploaded_file($profile_picture['tmp_name'], $target_file)) {
            // Update profile picture in the database
            $conn->query("UPDATE users SET profile_picture = '$target_file' WHERE username = '$username'");
        } else {
            $upload_error = "Failed to upload profile picture.";
        }
    }

    // Update username
    if (!empty($new_username) && $new_username !== $username) {
        $conn->query("UPDATE users SET username = '$new_username' WHERE username = '$username'");
        $_SESSION['user'] = $new_username; // Update session username
        header('Location: settings.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Warkop Panjalu</title>
    <link rel="stylesheet" href="settings.css">
</head>
<body>
    <div class="settings-container">
        <h1>Profile Settings</h1>
        <form method="POST" enctype="multipart/form-data">
            <div class="profile-picture">
                <img src="<?= htmlspecialchars($user['profile_picture'] ?? 'default-profile.png') ?>" alt="Profile Picture">
                <input type="file" name="profile_picture" accept="image/*">
                <?php if (isset($upload_error)): ?>
                    <p style="color: red;"><?= htmlspecialchars($upload_error) ?></p>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
            <div class="subscription-info">
                <p><strong>Subscription Duration:</strong> <?= $subscription_duration ?></p>
            </div>
            <button type="submit" name="update_profile" class="btn-update">Update Profile</button>
        </form>
        <a href="menu.php" class="btn-back">Back to Menu</a>
        <a href="dashboard.php" class="btn-dashboard">Go to Dashboard</a>
    </div>
</body>
</html>
