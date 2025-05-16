<?php
    // Database connection
    $host = 'localhost';
    $dbname = 'car_services';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    $currentYear = date('Y');

    // Get new cars (current or previous year)
    $newCars = $pdo->query("SELECT 
        CarID, 
        CarName, 
        Model, 
        Year, 
        Color, 
        IFNULL(Mileage, 0) AS Mileage, 
        IFNULL(Image, 'https://via.placeholder.com/400x250?text=No+Image') AS Image, 
        SalePrice,
        PricePerDay,
        Status
        FROM Car 
        WHERE Status IN ('available', 'rented')
        AND Year >= " . ($currentYear - 1) . "
        AND SalePrice IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);

    // Get rental cars
    $rentalCars = $pdo->query("SELECT 
        CarID, 
        CarName, 
        Model, 
        Year, 
        Color, 
        IFNULL(Mileage, 0) AS Mileage, 
        IFNULL(Image, 'https://via.placeholder.com/400x250?text=No+Image') AS Image, 
        SalePrice,
        PricePerDay,
        Status
        FROM Car 
        WHERE Status IN ('available', 'rented')
        AND PricePerDay IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);

    // Get used cars (older than previous year)
    $usedCars = $pdo->query("SELECT 
        CarID, 
        CarName, 
        Model, 
        Year, 
        Color, 
        IFNULL(Mileage, 0) AS Mileage, 
        IFNULL(Image, 'https://via.placeholder.com/400x250?text=No+Image') AS Image, 
        SalePrice,
        PricePerDay,
        Status
        FROM Car 
        WHERE Status IN ('available', 'rented')
        AND Year < " . ($currentYear - 1) . "
        AND SalePrice IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);

    // Function to display car cards
    function displayCarCards($cars, $type) {
        if (empty($cars)) {
            return '<div class="car-container"><p>No cars available at the moment.</p></div>';
        }
        
        $output = '<div class="car-container">';
        foreach ($cars as $car) {
            $carName = htmlspecialchars($car['CarName'] . ' ' . $car['Model'] . ' ' . $car['Year']);
            $price = ($type === 'Rent') ? number_format($car['PricePerDay'], 2) : number_format($car['SalePrice'], 2);
            $priceLabel = ($type === 'Rent') ? 'Rent: EGP ' . $price . ' / day' : 'Price: EGP ' . $price;
            $buttonText = ($type === 'Rent') ? 'Rent Now' : 'View Details';
            
            // Prepare available colors
            $colors = !empty($car['Color']) ? explode(',', $car['Color']) : ['Black'];
            $colorText = implode(', ', array_map('trim', $colors));
            
            // Prepare features
            $features = [];
            if ($car['Mileage'] > 0) $features[] = $car['Mileage'] . ' km';
            $featuresText = implode(', ', $features);
            
            $output .= '
            <div class="car-card" data-name="' . htmlspecialchars($car['CarName']) . '">
                <img src="' . htmlspecialchars($car['Image']) . '" alt="' . htmlspecialchars($car['CarName']) . '">
                <h3>' . $carName . '</h3>
                <p>' . $priceLabel . '</p>
                <button onclick="openDetails(\'' . addslashes($carName) . '\', \'' . addslashes($colorText) . '\', \'' . $price . '\', \'' . $type . '\', \'' . addslashes($featuresText) . '\')">' . $buttonText . '</button>
            </div>';
        }
        $output .= '</div>';
        
        return $output;
    }
    ?>