/*Office*/ 
INSERT INTO Office (Location, Phone, ManagerName, OpeningHours) VALUES
('Downtown Branch', '555-0101', 'John Smith', '8:00 AM - 8:00 PM'),
('Airport Branch', '555-0102', 'Sarah Johnson', '24/7'),
('Northside Branch', '555-0103', 'Michael Brown', '9:00 AM - 7:00 PM'),
('Westside Branch', '555-0104', 'Emily Davis', '8:30 AM - 7:30 PM'),
('Eastside Branch', '555-0105', 'Robert Wilson', '9:00 AM - 6:00 PM');

/*Car Data*/
INSERT INTO Car (CarName, Model, Year, Color, Mileage, PlateNumber, Status, PricePerDay, SalePrice, Image, OfficeID) VALUES
('Toyota', 'Camry', 2022, 'Silver', 12000, 'ABC123', 'available', 59.99, 25000.00, 'camry.jpg', 1),
('Honda', 'Accord', 2021, 'Black', 18000, 'DEF456', 'rented', 65.99, 27000.00, 'accord.jpg', 2),
('Ford', 'Mustang', 2023, 'Red', 5000, 'GHI789', 'available', 99.99, 45000.00, 'mustang.jpg', 3),
('BMW', 'X5', 2022, 'White', 15000, 'JKL012', 'maintenance', 129.99, 65000.00, 'x5.jpg', 1),
('Tesla', 'Model 3', 2023, 'Blue', 8000, 'MNO345', 'available', 89.99, 48000.00, 'model3.jpg', 4),
('Chevrolet', 'Tahoe', 2021, 'Black', 22000, 'PQR678', 'available', 79.99, 52000.00, 'tahoe.jpg', 2),
('Mercedes', 'C-Class', 2022, 'Silver', 10000, 'STU901', 'sold', 109.99, 58000.00, 'cclass.jpg', 3),
('Nissan', 'Altima', 2023, 'Gray', 7000, 'VWX234', 'available', 55.99, 28000.00, 'altima.jpg', 5),
('Audi', 'Q7', 2021, 'Black', 25000, 'YZA567', 'available', 119.99, 62000.00, 'q7.jpg', 4),
('Hyundai', 'Elantra', 2023, 'White', 3000, 'BCD890', 'available', 49.99, 23000.00, 'elantra.jpg', 1);

/*Customer Data*/
INSERT INTO Customer (Name, Email, Phone, Address, Password, RegistrationDate, Status, DrivingLicense) VALUES
('David Miller', 'david.miller@email.com', '555-0201', '123 Main St, Anytown', 'password123', '2023-01-15 10:30:00', 'active', 'DL12345678'),
('Lisa Johnson', 'lisa.j@email.com', '555-0202', '456 Oak Ave, Somewhere', 'securepass', '2023-02-20 14:15:00', 'active', 'DL23456789'),
('James Wilson', 'james.w@email.com', '555-0203', '789 Pine Rd, Nowhere', 'jamespass', '2023-03-10 09:00:00', 'active', 'DL34567890'),
('Sarah Davis', 'sarah.d@email.com', '555-0204', '321 Elm St, Anycity', 'sarah1234', '2023-01-25 16:45:00', 'inactive', 'DL45678901'),
('Robert Brown', 'robert.b@email.com', '555-0205', '654 Maple Dr, Yourtown', 'brownie22', '2023-04-05 11:20:00', 'active', 'DL56789012'),
('Emily Taylor', 'emily.t@email.com', '555-0206', '987 Cedar Ln, Mytown', 'emilypass', '2023-02-28 13:10:00', 'active', 'DL67890123'),
('Michael Clark', 'michael.c@email.com', '555-0207', '159 Birch Blvd, Histown', 'mike2023', '2023-03-15 08:30:00', 'active', 'DL78901234'),
('Jennifer Lee', 'jennifer.l@email.com', '555-0208', '753 Spruce Way, Theirtown', 'jennylee', '2023-01-10 15:50:00', 'inactive', 'DL89012345'),
('Daniel Harris', 'daniel.h@email.com', '555-0209', '357 Willow Cir, Ourcity', 'dannyh', '2023-04-20 12:00:00', 'active', 'DL90123456'),
('Jessica Martin', 'jessica.m@email.com', '555-0210', '852 Aspen Ct, Newtown', 'jessm2023', '2023-02-05 17:25:00', 'active', 'DL01234567');

/*Rental  Data*/
INSERT INTO Rental (CustomerID, CarID, StartDate, EndDate, TotalCost, Status, PickupLocation, ReturnLocation) VALUES
(1, 2, '2023-05-01', '2023-05-05', 263.96, 'completed', 2, 2),
(3, 1, '2023-05-10', '2023-05-15', 299.95, 'completed', 1, 1),
(5, 3, '2023-05-20', '2023-05-25', 499.95, 'active', 3, NULL),
(2, 6, '2023-06-01', '2023-06-07', 559.93, 'reserved', 2, 4),
(7, 5, '2023-05-15', '2023-05-20', 449.95, 'completed', 4, 4),
(4, 8, '2023-05-25', '2023-05-30', 279.95, 'cancelled', 5, NULL),
(6, 9, '2023-06-05', '2023-06-12', 839.93, 'reserved', 4, 4),
(8, 10, '2023-05-18', '2023-05-22', 199.96, 'completed', 1, 1),
(9, 4, '2023-06-10', '2023-06-15', 649.95, 'reserved', 1, 3),
(10, 7, '2023-05-05', '2023-05-10', 549.95, 'completed', 3, 3);

/*Sale  Data*/
INSERT INTO Sale (CarID, CustomerID, SaleDate, SalePrice, PaymentMethod, Status) VALUES
(7, 4, '2023-04-15 11:30:00', 55000.00, 'credit', 'completed'),
(3, 2, '2023-05-12 14:45:00', 44000.00, 'bank_transfer', 'completed'),
(1, 8, '2023-05-20 10:15:00', 24500.00, 'cash', 'completed'),
(5, 6, '2023-06-01 16:20:00', 47000.00, 'credit', 'pending'),
(9, 3, '2023-05-25 09:30:00', 60000.00, 'bank_transfer', 'completed'),
(2, 10, '2023-06-05 13:00:00', 26500.00, 'paypal', 'pending'),
(4, 5, '2023-05-18 15:45:00', 63000.00, 'credit', 'completed'),
(6, 7, '2023-06-10 11:10:00', 50000.00, 'cash', 'pending'),
(8, 1, '2023-05-30 14:30:00', 27000.00, 'bank_transfer', 'completed'),
(10, 9, '2023-06-15 10:00:00', 22000.00, 'credit', 'pending');

/*Maintenance   Data*/
INSERT INTO Maintenance (CarID, MaintenanceDate, MaintenanceType, Description, Cost, Status, WorkshopName, WorkshopContact, ExpectedCompletionDate) VALUES
(4, '2023-05-10 09:00:00', 'Engine Check', 'Routine engine maintenance and oil change', 250.00, 'completed', 'City Auto Care', '555-0301', '2023-05-10'),
(2, '2023-05-15 10:30:00', 'Brake Replacement', 'Replace front and rear brake pads', 350.00, 'completed', 'Quick Fix Garage', '555-0302', '2023-05-15'),
(6, '2023-05-20 08:00:00', 'Tire Rotation', 'Rotate tires and check alignment', 120.00, 'completed', 'Tire Masters', '555-0303', '2023-05-20'),
(1, '2023-06-01 11:00:00', 'Transmission Service', 'Full transmission service', 450.00, 'in_progress', 'Transmission Experts', '555-0304', '2023-06-05'),
(9, '2023-05-25 13:30:00', 'Electrical System', 'Check wiring and electrical components', 300.00, 'pending', 'Auto Electric', '555-0305', '2023-05-30'),
(3, '2023-06-05 14:00:00', 'Suspension Check', 'Inspect and repair suspension system', 400.00, 'pending', 'Suspension Pros', '555-0306', '2023-06-10'),
(5, '2023-05-18 09:30:00', 'AC Service', 'AC system recharge and check', 180.00, 'completed', 'Cool Air Auto', '555-0307', '2023-05-18'),
(7, '2023-06-10 10:00:00', 'Body Repair', 'Fix minor dents and scratches', 600.00, 'pending', 'Body Works', '555-0308', '2023-06-15'),
(8, '2023-05-30 08:30:00', 'Oil Change', 'Standard oil and filter change', 80.00, 'completed', 'Express Lube', '555-0309', '2023-05-30'),
(10, '2023-06-15 07:30:00', 'Battery Replacement', 'Replace old battery with new one', 200.00, 'pending', 'Battery World', '555-0310', '2023-06-15');

/*Payment  Data*/
INSERT INTO Payment (RentalID, SaleID, Amount, PaymentDate, TransactionID, PaymentMethod, Status) VALUES
(1, NULL, 263.96, '2023-05-01 10:15:00', 'PAY123456', 'credit_card', 'completed'),
(NULL, 1, 55000.00, '2023-04-15 11:35:00', 'PAY234567', 'credit_card', 'completed'),
(2, NULL, 299.95, '2023-05-10 09:30:00', 'PAY345678', 'credit_card', 'completed'),
(NULL, 2, 44000.00, '2023-05-12 15:00:00', 'PAY456789', 'bank_transfer', 'completed'),
(3, NULL, 499.95, '2023-05-20 08:45:00', 'PAY567890', 'paypal', 'completed'),
(NULL, 3, 24500.00, '2023-05-20 10:20:00', 'PAY678901', 'cash', 'completed'),
(4, NULL, 559.93, '2023-06-01 14:30:00', 'PAY789012', 'credit_card', 'pending'),
(NULL, 4, 47000.00, '2023-06-01 16:25:00', 'PAY890123', 'credit_card', 'pending'),
(5, NULL, 449.95, '2023-05-15 11:20:00', 'PAY901234', 'bank_transfer', 'completed'),
(NULL, 5, 60000.00, '2023-05-25 09:35:00', 'PAY012345', 'bank_transfer', 'completed');

/*CustomerPurchase   Data*/
INSERT INTO CustomerPurchase (CustomerID, CarID, PurchaseDate, FinalPrice, PaymentMethod, PaymentStatus, DeliveryAddress) VALUES
(4, 7, '2023-04-15 11:40:00', 55000.00, 'credit_card', 'completed', '321 Elm St, Anycity'),
(2, 3, '2023-05-12 15:05:00', 44000.00, 'bank_transfer', 'completed', '456 Oak Ave, Somewhere'),
(8, 1, '2023-05-20 10:25:00', 24500.00, 'cash', 'completed', '753 Spruce Way, Theirtown'),
(6, 5, '2023-06-01 16:30:00', 47000.00, 'credit_card', 'pending', '987 Cedar Ln, Mytown'),
(3, 9, '2023-05-25 09:40:00', 60000.00, 'bank_transfer', 'completed', '789 Pine Rd, Nowhere'),
(10, 2, '2023-06-05 13:05:00', 26500.00, 'paypal', 'pending', '852 Aspen Ct, Newtown'),
(5, 4, '2023-05-18 15:50:00', 63000.00, 'credit_card', 'completed', '654 Maple Dr, Yourtown'),
(7, 6, '2023-06-10 11:15:00', 50000.00, 'cash', 'pending', '159 Birch Blvd, Histown'),
(1, 8, '2023-05-30 14:35:00', 27000.00, 'bank_transfer', 'completed', '123 Main St, Anytown'),
(9, 10, '2023-06-15 10:05:00', 22000.00, 'credit_card', 'pending', '357 Willow Cir, Ourcity');

/*Admin  Data*/
INSERT INTO Admin (Name, Email, Password, Role, Status, RegisteredAt, LastLogin) VALUES
('Admin Super', 'super.admin@carservices.com', 'superpass123', 'superadmin', 'active', '2023-01-01 09:00:00', '2023-06-01 14:30:00'),
('Branch Manager', 'manager.downtown@carservices.com', 'managerpass', 'manager', 'active', '2023-01-05 10:15:00', '2023-06-01 15:45:00'),
('Sales Admin', 'sales.admin@carservices.com', 'salespass123', 'admin', 'active', '2023-01-10 11:30:00', '2023-06-01 16:20:00'),
('Rental Admin', 'rental.admin@carservices.com', 'rentalpass', 'admin', 'active', '2023-01-15 13:45:00', '2023-06-01 10:10:00'),
('Maintenance Admin', 'maintenance.admin@carservices.com', 'maintenancepass', 'admin', 'active', '2023-01-20 14:00:00', '2023-06-01 09:30:00'),
('Inactive Admin', 'inactive.admin@carservices.com', 'inactivepass', 'admin', 'inactive', '2023-02-01 08:30:00', '2023-05-15 17:00:00'),
('Airport Manager', 'manager.airport@carservices.com', 'airportpass', 'manager', 'active', '2023-02-10 09:45:00', '2023-06-01 11:45:00'),
('Northside Manager', 'manager.north@carservices.com', 'northpass', 'manager', 'active', '2023-02-15 10:30:00', '2023-06-01 13:15:00'),
('Support Admin', 'support.admin@carservices.com', 'supportpass', 'admin', 'active', '2023-03-01 11:00:00', '2023-06-01 14:00:00'),
('Finance Admin', 'finance.admin@carservices.com', 'financepass', 'admin', 'active', '2023-03-10 12:15:00', '2023-06-01 15:30:00');

/*Feedback   Data*/
INSERT INTO Feedback (CustomerID, RentalID, SaleID, Rating, Comments, SubmissionDate, Response) VALUES
(1, 1, NULL, 5, 'Excellent service and clean car!', '2023-05-06 12:30:00', 'Thank you for your feedback!'),
(3, 2, NULL, 4, 'Good experience overall, but pickup took longer than expected', '2023-05-16 14:45:00', 'We appreciate your feedback and will work on improving our pickup process.'),
(NULL, NULL, 1, 3, 'Car was as described but sales process was slow', '2023-04-16 10:20:00', 'Thank you for your honest feedback. We are working to streamline our sales process.'),
(5, 3, NULL, 5, 'Amazing car and great rental experience!', '2023-05-21 09:15:00', 'We are glad you enjoyed your rental!'),
(7, 5, NULL, 4, 'Smooth rental process, would use again', '2023-05-21 16:30:00', 'Thank you for choosing us!'),
(NULL, NULL, 3, 5, 'Perfect car at a great price!', '2023-05-21 11:40:00', 'We are happy you are satisfied with your purchase!'),
(8, 8, NULL, 2, 'Car had some issues that weren''t mentioned', '2023-05-23 15:20:00', 'We apologize for the inconvenience. Please contact our support team.'),
(10, 10, NULL, 4, 'Good service and friendly staff', '2023-05-11 13:10:00', 'Thank you for your kind words!'),
(NULL, NULL, 5, 5, 'Excellent buying experience from start to finish', '2023-05-26 10:50:00', 'We appreciate your business!'),
(6, 7, NULL, 3, 'Average experience, nothing special', '2023-06-02 17:00:00', 'Thank you for your feedback. We will strive to improve.');

/*Contact    Data*/
INSERT INTO Contact (Name, Email, Phone, Subject, Message, SubmissionDate, Status, AssignedTo, Response) VALUES
('Customer One', 'customer.one@email.com', '555-0401', 'Question about rental', 'What documents do I need to rent a car?', '2023-05-10 11:20:00', 'resolved', 3, 'You need a valid driver''s license and credit card.'),
('Customer Two', 'customer.two@email.com', '555-0402', 'Complaint', 'My rental car was dirty when I picked it up', '2023-05-15 14:30:00', 'resolved', 4, 'We apologize for the inconvenience. A discount has been applied to your account.'),
('Customer Three', 'customer.three@email.com', '555-0403', 'Sales inquiry', 'Do you offer financing options?', '2023-05-20 10:15:00', 'in_progress', 2, 'Yes, we partner with several financing companies. Details have been sent to your email.'),
('Customer Four', 'customer.four@email.com', '555-0404', 'Maintenance question', 'Where can I get my rental car serviced?', '2023-05-25 09:45:00', 'new', NULL, NULL),
('Customer Five', 'customer.five@email.com', '555-0405', 'Feedback', 'Your website is difficult to use', '2023-06-01 16:20:00', 'in_progress', 9, 'Thank you for your feedback. We are currently redesigning our website.'),
('Customer Six', 'customer.six@email.com', '555-0406', 'Insurance question', 'What insurance options do you offer?', '2023-06-05 11:30:00', 'new', NULL, NULL),
('Customer Seven', 'customer.seven@email.com', '555-0407', 'Complaint', 'I was charged incorrectly', '2023-05-18 13:45:00', 'resolved', 10, 'We have reviewed your case and issued a refund for the overcharge.'),
('Customer Eight', 'customer.eight@email.com', '555-0408', 'General question', 'Do you have electric vehicles?', '2023-05-30 15:10:00', 'resolved', 3, 'Yes, we have several electric vehicle options including Tesla models.'),
('Customer Nine', 'customer.nine@email.com', '555-0409', 'Corporate inquiry', 'Do you offer corporate discounts?', '2023-06-10 10:30:00', 'in_progress', 2, NULL),
('Customer Ten', 'customer.ten@email.com', '555-0410', 'Technical issue', 'Cannot login to my account', '2023-06-15 09:15:00', 'new', NULL, NULL);

/*Orders     Data*/
INSERT INTO orders (client_name, car_info, type, date, status, price) VALUES
('David Miller', 'Honda Accord 2021', 'rental', '2023-05-01', 'completed', 263.96),
('Lisa Johnson', 'Ford Mustang 2023', 'sale', '2023-05-12', 'completed', 44000.00),
('James Wilson', 'Toyota Camry 2022', 'rental', '2023-05-10', 'completed', 299.95),
('Robert Brown', 'Chevrolet Tahoe 2021', 'repair', '2023-05-20', 'inprogress', 350.00),
('Emily Taylor', 'Tesla Model 3 2023', 'buy', '2023-06-01', 'pending', 47000.00),
('Michael Clark', 'Nissan Altima 2023', 'rental', '2023-05-25', 'canceled', 279.95),
('Jennifer Lee', 'Audi Q7 2021', 'sale', '2023-05-25', 'completed', 60000.00),
('Daniel Harris', 'Hyundai Elantra 2023', 'rental', '2023-05-18', 'completed', 199.96),
('Jessica Martin', 'BMW X5 2022', 'repair', '2023-06-10', 'pending', 600.00),
('Sarah Davis', 'Mercedes C-Class 2022', 'buy', '2023-04-15', 'completed', 55000.00);

