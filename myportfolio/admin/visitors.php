<?php
session_start();
date_default_timezone_set('Asia/Karachi');
require_once '../config.php';

$password = 'Mysite2@#';

// Handle delete actions
if (isset($_SESSION['admin_logged_in'])) {
    // Delete single record
    if (isset($_GET['delete_id']) && isset($_GET['type'])) {
        $id = intval($_GET['delete_id']);
        $type = $_GET['type'];
        
        if ($type === 'visitor') {
            $conn->query("DELETE FROM visitor_logs WHERE id = $id");
        } elseif ($type === 'message') {
            $conn->query("DELETE FROM contact_messages WHERE id = $id");
        }
        
        header('Location: ?section=' . ($_GET['section'] ?? 'visitors'));
        exit;
    }
    
    // Delete all records by IP
    if (isset($_GET['delete_ip']) && isset($_GET['type'])) {
        $ip = $_GET['delete_ip'];
        $type = $_GET['type'];
        
        if ($type === 'visitor') {
            $stmt = $conn->prepare("DELETE FROM visitor_logs WHERE ip_address = ?");
            $stmt->bind_param("s", $ip);
            $stmt->execute();
        } elseif ($type === 'message') {
            $stmt = $conn->prepare("DELETE FROM contact_messages WHERE ip_address = ?");
            $stmt->bind_param("s", $ip);
            $stmt->execute();
        }
        
        header('Location: ?section=' . ($_GET['section'] ?? 'visitors'));
        exit;
    }
    
    // Clear all logs
    if (isset($_GET['clear_all']) && isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        $type = $_GET['clear_all'];
        
        if ($type === 'visitors') {
            $conn->query("TRUNCATE TABLE visitor_logs");
        } elseif ($type === 'messages') {
            $conn->query("TRUNCATE TABLE contact_messages");
        }
        
        header('Location: ?section=' . $type);
        exit;
    }
}

// Check if logout requested
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?');
    exit;
}

// Check if already logged in
if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === $password) {
            $_SESSION['admin_logged_in'] = true;
            header('Location: ?');
            exit;
        } else {
            $error = "Incorrect password";
        }
    }
    
    // Show login form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login</title>
        <style>
            body { font-family: Arial; background: #f5f5f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .login-box { background: white; padding: 40px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 300px; }
            h3 { text-align: center; color: #333; margin-bottom: 20px; }
            input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; }
            button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 16px; }
            button:hover { background: #0056b3; }
            .error { color: red; text-align: center; margin-bottom: 10px; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h3>Admin Login</h3>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter admin password" required autofocus>
                <button type="submit">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// User is logged in - get section and search parameters
$section = $_GET['section'] ?? 'visitors';
$search = $_GET['search'] ?? '';

?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .logout a { padding: 8px 15px; background: #dc3545; color: white; text-decoration: none; border-radius: 3px; }
        .logout a:hover { background: #c82333; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; }
        .tab-btn { padding: 10px 20px; background: none; border: none; cursor: pointer; font-size: 16px; border-bottom: 3px solid transparent; color: #666; }
        .tab-btn.active { border-bottom-color: #007bff; color: #007bff; font-weight: bold; }
        .tab-btn:hover { color: #007bff; }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-box { padding: 15px; background: #f9f9f9; border-left: 4px solid #007bff; border-radius: 3px; min-width: 150px; }
        .stat-box h3 { font-size: 14px; color: #666; margin-bottom: 5px; }
        .stat-box p { font-size: 28px; color: #007bff; font-weight: bold; }
        .filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
        .filters input { padding: 8px; border: 1px solid #ddd; border-radius: 3px; width: 250px; }
        .filters button { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
        .filters button:hover { background: #0056b3; }
        .clear-all-btn { padding: 8px 15px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; }
        .clear-all-btn:hover { background: #c82333; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; overflow-x: auto; }
        th { background: #f9f9f9; padding: 12px; text-align: left; border-bottom: 2px solid #ddd; font-weight: bold; color: #333; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        tr:hover { background: #f9f9f9; }
        .map-link { color: #007bff; text-decoration: none; }
        .map-link:hover { text-decoration: underline; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; font-size: 12px; }
        .message-preview { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .badge { display: inline-block; padding: 4px 8px; background: #e8f5e9; color: #388e3c; border-radius: 3px; font-size: 12px; }
        .badge.repeat { background: #fff3e0; color: #f57c00; }
        .action-links { display: flex; gap: 5px; }
        .action-links a { color: #dc3545; text-decoration: none; font-size: 12px; padding: 4px 8px; border: 1px solid #dc3545; border-radius: 3px; }
        .action-links a:hover { background: #dc3545; color: white; }
        .action-links a.delete-all { color: #ff9800; border-color: #ff9800; }
        .action-links a.delete-all:hover { background: #ff9800; color: white; }
    </style>
    <script>
        function confirmDelete(message) {
            return confirm(message || 'Are you sure you want to delete this record?');
        }
        
        function confirmDeleteIP(ip) {
            return confirm('Delete ALL records from IP: ' + ip + '?\n\nThis will delete all visitor logs and messages from this IP address.');
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header-top">
            <h1>Admin Dashboard</h1>
            <div class="logout">
                <a href="?logout=1">Logout</a>
            </div>
        </div>

        <div class="tabs">
            <button class="tab-btn <?php echo $section === 'visitors' ? 'active' : ''; ?>" onclick="location.href='?section=visitors'">Visitor Logs</button>
            <button class="tab-btn <?php echo $section === 'messages' ? 'active' : ''; ?>" onclick="location.href='?section=messages'">Contact Messages</button>
        </div>

        <?php if ($section === 'visitors'): ?>
            <?php
            $query = "SELECT * FROM visitor_logs WHERE 1=1";
            $params = [];
            $types = '';

            if (!empty($search)) {
                $query .= " AND (ip_address LIKE ? OR country LIKE ? OR city LIKE ?)";
                $search_param = '%' . $search . '%';
                $params = [$search_param, $search_param, $search_param];
                $types = 'sss';
            }

            $query .= " ORDER BY visit_time DESC LIMIT 500";

            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $visitors = $result->fetch_all(MYSQLI_ASSOC);
            $total = count($visitors);

            $total_all = $conn->query("SELECT COUNT(*) as count FROM visitor_logs")->fetch_assoc();
            $repeat = $conn->query("SELECT COUNT(*) as count FROM visitor_logs WHERE is_repeat_visitor = 1")->fetch_assoc();
            ?>

            <div class="stats">
                <div class="stat-box">
                    <h3>Total Visitors</h3>
                    <p><?php echo $total_all['count']; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Repeat Visitors</h3>
                    <p><?php echo $repeat['count']; ?></p>
                </div>
                <div class="stat-box">
                    <h3>New Visitors</h3>
                    <p><?php echo $total_all['count'] - $repeat['count']; ?></p>
                </div>
            </div>

            <div class="filters">
                <form method="GET" style="display: flex; gap: 10px;">
                    <input type="hidden" name="section" value="visitors">
                    <input type="text" name="search" placeholder="Search IP, country, city..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                       
                        <th>IP Address</th>
                        <th>Country</th>
                        <th>City</th>
                        <th>Region</th>
                        <th>Location</th>
                        <th>Browser</th>
                        <th>OS</th>
                        <th>Device</th>
                        <th>Page</th>
                        <th>Resolution</th>
                        <th>Status</th>
                        <th>Visit Time</th>
                         <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visitors as $visitor): ?>
                        <tr>
                            
                            <td><code><?php echo htmlspecialchars($visitor['ip_address']); ?></code></td>
                            <td><?php echo htmlspecialchars($visitor['country'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($visitor['city'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($visitor['region'] ?? 'Unknown'); ?></td>
                            <td>
                                <?php if (!empty($visitor['latitude']) && !empty($visitor['longitude'])): ?>
                                    <a href="https://maps.google.com/?q=<?php echo $visitor['latitude']; ?>,<?php echo $visitor['longitude']; ?>" target="_blank" class="map-link">Map</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($visitor['browser'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($visitor['os'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($visitor['device_type'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($visitor['page_visited'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($visitor['screen_resolution'] ?? 'Unknown'); ?></td>
                            <td>
                                <?php if ($visitor['is_repeat_visitor']): ?>
                                    <span class="badge repeat">Repeat</span>
                                <?php else: ?>
                                    <span class="badge">New</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('h:i a, d M Y', strtotime($visitor['visit_time'])); ?></td>
                            <td class="action-links">
                                <a href="?delete_id=<?php echo $visitor['id']; ?>&type=visitor&section=visitors" onclick="return confirmDelete()">Delete</a>
                                <a href="?delete_ip=<?php echo urlencode($visitor['ip_address']); ?>&type=visitor&section=visitors" class="delete-all" onclick="return confirmDeleteIP('<?php echo htmlspecialchars($visitor['ip_address']); ?>')">Delete IP</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <?php
            $query = "SELECT * FROM contact_messages WHERE 1=1";
            $params = [];
            $types = '';

            if (!empty($search)) {
                $query .= " AND (email LIKE ? OR name LIKE ? OR city LIKE ?)";
                $search_param = '%' . $search . '%';
                $params = [$search_param, $search_param, $search_param];
                $types = 'sss';
            }

            $query .= " ORDER BY submission_time DESC LIMIT 500";

            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $messages = $result->fetch_all(MYSQLI_ASSOC);
            $total = count($messages);

            $total_all = $conn->query("SELECT COUNT(*) as count FROM contact_messages")->fetch_assoc();
            ?>

            <div class="stats">
                <div class="stat-box">
                    <h3>Total Messages</h3>
                    <p><?php echo $total_all['count']; ?></p>
                </div>
            </div>

            <div class="filters">
                <form method="GET" style="display: flex; gap: 10px;">
                    <input type="hidden" name="section" value="messages">
                    <input type="text" name="search" placeholder="Search email, name, city..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>IP Address</th>
                        <th>Country</th>
                        <th>City</th>
                        <th>Location</th>
                        <th>Browser</th>
                        <th>Device</th>
                        <th>Message</th>
                        <th>Time</th>
                        <th>Actions</th>

                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                        <tr>
                            
                            <td><?php echo htmlspecialchars($msg['name']); ?></td>
                            <td><code><?php echo htmlspecialchars($msg['email']); ?></code></td>
                            <td><?php echo htmlspecialchars($msg['phone'] ?? 'N/A'); ?></td>
                            <td><code><?php echo htmlspecialchars($msg['ip_address']); ?></code></td>
                            <td><?php echo htmlspecialchars($msg['country'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($msg['city'] ?? 'Unknown'); ?></td>
                            <td>
                                <?php if (!empty($msg['latitude']) && !empty($msg['longitude'])): ?>
                                    <a href="https://maps.google.com/?q=<?php echo $msg['latitude']; ?>,<?php echo $msg['longitude']; ?>" target="_blank" class="map-link">Map</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($msg['browser'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($msg['device_type'] ?? 'Unknown'); ?></td>
                            <td class="message-preview" title="<?php echo htmlspecialchars($msg['message']); ?>">
                                <?php echo htmlspecialchars(substr($msg['message'], 0, 40)); ?>...
                            </td>
                            <td><?php echo date('h:i a, d M Y', strtotime($msg['submission_time'])); ?></td>
                            <td class="action-links">
                                <a href="?delete_id=<?php echo $msg['id']; ?>&type=message&section=messages" onclick="return confirmDelete()">Delete</a>
                                <a href="?delete_ip=<?php echo urlencode($msg['ip_address']); ?>&type=message&section=messages" class="delete-all" onclick="return confirmDeleteIP('<?php echo htmlspecialchars($msg['ip_address']); ?>')">Delete IP</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </div>
</body>
</html>