<?php
// admin_functions.php

// Database connection
function connectToDatabase() {
    $host = 'localhost';
    $dbname = 'car_services';
    $username = 'root';
    $password = '';

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Get dashboard statistics
function getDashboardStatistics() {
    $conn = connectToDatabase();
    
    $stats = [];
    
    // Available cars count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Car WHERE Status = 'available'");
    $stmt->execute();
    $stats['available_cars'] = $stmt->fetchColumn();
    
    // Today's sales
    $stmt = $conn->prepare("SELECT SUM(SalePrice) FROM Sale WHERE DATE(SaleDate) = CURDATE() AND Status = 'completed'");
    $stmt->execute();
    $stats['today_sales'] = $stmt->fetchColumn() ?? 0;
    
    // Active rentals
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Rental WHERE Status = 'active'");
    $stmt->execute();
    $stats['active_rentals'] = $stmt->fetchColumn();
    
    // Maintenance jobs
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Maintenance WHERE Status != 'completed'");
    $stmt->execute();
    $stats['maintenance_jobs'] = $stmt->fetchColumn();
    
    return $stats;
}

// Get recent car listings
function getRecentCarListings($limit = 5) {
    $conn = connectToDatabase();
    
    $stmt = $conn->prepare("
        SELECT c.CarID, c.CarName, c.Model, c.Year, c.PricePerDay, c.SalePrice, c.Status, o.Location 
        FROM Car c
        LEFT JOIN Office o ON c.OfficeID = o.OfficeID
        ORDER BY c.CarID DESC
        LIMIT :limit
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get upcoming maintenance
function getUpcomingMaintenance($limit = 5) {
    $conn = connectToDatabase();
    
    $stmt = $conn->prepare("
        SELECT m.MaintenanceID, c.CarName, c.Model, c.PlateNumber, 
               cust.Name AS CustomerName, m.MaintenanceType, 
               m.MaintenanceDate, m.Status, m.ExpectedCompletionDate
        FROM Maintenance m
        JOIN Car c ON m.CarID = c.CarID
        LEFT JOIN Rental r ON c.CarID = r.CarID AND r.Status = 'active'
        LEFT JOIN Customer cust ON r.CustomerID = cust.CustomerID
        WHERE m.Status != 'completed'
        ORDER BY m.ExpectedCompletionDate ASC
        LIMIT :limit
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get monthly sales and rentals data
function getMonthlySalesRentalsData() {
    $conn = connectToDatabase();
    
    $data = [
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        'sales' => [],
        'rentals' => []
    ];
    
    // Get sales data (simplified - in a real app you'd query actual data)
    for ($i = 1; $i <= 6; $i++) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM Sale 
            WHERE MONTH(SaleDate) = :month AND YEAR(SaleDate) = YEAR(CURDATE())
            AND Status = 'completed'
        ");
        $stmt->bindParam(':month', $i);
        $stmt->execute();
        $data['sales'][] = $stmt->fetchColumn() * rand(2, 5); // Multiply to simulate realistic numbers
    }
    
    // Get rentals data (simplified)
    for ($i = 1; $i <= 6; $i++) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM Rental 
            WHERE MONTH(StartDate) = :month AND YEAR(StartDate) = YEAR(CURDATE())
            AND Status IN ('active', 'completed')
        ");
        $stmt->bindParam(':month', $i);
        $stmt->execute();
        $data['rentals'][] = $stmt->fetchColumn() * rand(1, 3);
    }
    
    return $data;
}

// Get inventory by type data
function getInventoryByTypeData() {
    $conn = connectToDatabase();
    
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN Model LIKE '%Sedan%' THEN 1 ELSE 0 END) as sedan,
            SUM(CASE WHEN Model LIKE '%SUV%' THEN 1 ELSE 0 END) as suv,
            SUM(CASE WHEN Model LIKE '%Truck%' THEN 1 ELSE 0 END) as truck,
            SUM(CASE WHEN Model LIKE '%Sports%' THEN 1 ELSE 0 END) as sports,
            SUM(CASE WHEN Model LIKE '%Electric%' THEN 1 ELSE 0 END) as electric
        FROM Car
        WHERE Status != 'sold'
    ");
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get notifications
function getNotifications($type = 'unread') {
    // In a real app, you'd query the database for actual notifications
    // This is a simplified version
    $notifications = [
        'unread' => [
            ['id' => 1, 'message' => 'New car listing added: Tesla Model 3', 'date' => date('Y-m-d')],
            ['id' => 2, 'message' => 'Test drive scheduled for BMW X5', 'date' => date('Y-m-d')],
            ['id' => 3, 'message' => 'Maintenance completed for Ford F-150', 'date' => date('Y-m-d')]
        ],
        'read' => [
            ['id' => 4, 'message' => 'Payment received for Toyota Camry', 'date' => date('Y-m-d', strtotime('-1 day'))],
            ['id' => 5, 'message' => 'New rental agreement signed', 'date' => date('Y-m-d', strtotime('-2 days'))]
        ]
    ];
    
    if ($type === 'all') {
        return array_merge($notifications['unread'], $notifications['read']);
    }
    
    return $notifications[$type] ?? [];
}

// Get messages
function getMessages($type = 'inbox') {
    // In a real app, you'd query the database for actual messages
    // This is a simplified version
    $messages = [
        'inbox' => [
            ['id' => 1, 'from' => 'John Smith', 'subject' => 'Inquiry about Toyota Camry', 'date' => date('Y-m-d')],
            ['id' => 2, 'from' => 'Car Dealer Network', 'subject' => 'New inventory available', 'date' => date('Y-m-d', strtotime('-1 day'))]
        ],
        'sent' => [
            ['id' => 3, 'to' => 'Service Department', 'subject' => 'Urgent: Brake parts needed', 'date' => date('Y-m-d')],
            ['id' => 4, 'to' => 'Sales Team', 'subject' => 'Monthly targets update', 'date' => date('Y-m-d', strtotime('-1 day'))]
        ],
        'important' => [
            ['id' => 5, 'from' => 'Finance Department', 'subject' => 'Quarterly report review', 'date' => date('Y-m-d', strtotime('-2 days'))],
            ['id' => 6, 'from' => 'CEO', 'subject' => 'Strategy meeting next week', 'date' => date('Y-m-d', strtotime('-3 days'))]
        ]
    ];
    
    return $messages[$type] ?? [];
}

// Helper function to format car status badge
function getStatusBadge($status) {
    $badgeClasses = [
        'available' => 'badge-success',
        'rented' => 'badge-info',
        'maintenance' => 'badge-warning',
        'sold' => 'badge-danger',
        'reserved' => 'badge-warning'
    ];
    
    $statusText = ucfirst($status);
    $badgeClass = $badgeClasses[strtolower($status)] ?? 'badge-secondary';
    
    return "<span class='badge $badgeClass'>$statusText</span>";
}
?>