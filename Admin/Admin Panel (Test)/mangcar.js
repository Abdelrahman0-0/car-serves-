$(document).ready(function() {
    $('#carsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/en-GB.json'
        },
        dom: 'lrtip',
        initComplete: function() {
            $('.dataTables_filter').hide();
        }
    });
    
    $('#carSearch').on('keyup', function() {
        $('#carsTable').DataTable().search($(this).val()).draw();
    });
});

function updatePriceField() {
    const status = document.getElementById('carStatus').value;
    const dailyPriceContainer = document.getElementById('dailyPriceContainer');
    const salePriceContainer = document.getElementById('salePriceContainer');

    dailyPriceContainer.style.display = 'none';
    salePriceContainer.style.display = 'none';

    if (status === 'Available' || status === 'Reserved') {
        dailyPriceContainer.style.display = 'block';
    } else if (status === 'Sold' || status === 'Purchased') {
        salePriceContainer.style.display = 'block';
    }
}

function showAddCarModal() {
    Swal.fire({
        title: 'Add New Car',
        html: `
            <form id="addCarForm">
                <div class="mb-3">
                    <label for="carName" class="form-label">Car Name</label>
                    <input type="text" class="form-control" id="carName" required>
                </div>
                <div class="mb-3">
                    <label for="carModel" class="form-label">Model</label>
                    <input type="text" class="form-control" id="carModel" required>
                </div>
                <div class="mb-3">
                    <label for="carYear" class="form-label">Year</label>
                    <input type="number" class="form-control" id="carYear" min="2000" max="2023" required>
                </div>
                <div class="mb-3">
                    <label for="carColor" class="form-label">Color</label>
                    <input type="text" class="form-control" id="carColor" required>
                </div>
                <div class="mb-3">
                    <label for="carPlate" class="form-label">License Plate</label>
                    <input type="text" class="form-control" id="carPlate" required>
                </div>
                <div class="mb-3">
                    <label for="carStatus" class="form-label">Status</label>
                    <select class="form-select" id="carStatus" onchange="updatePriceField()" required>
                        <option value="Available" selected>Available</option>
                        <option value="Reserved">Reserved</option>
                        <option value="Sold">Sold</option>
                        <option value="Out of Service">Out of Service</option>
                    </select>
                </div>
                <div id="dailyPriceContainer" class="mb-3">
                    <label for="carDailyPrice" class="form-label">Daily Price ($)</label>
                    <input type="number" class="form-control" id="carDailyPrice" min="1">
                </div>
                <div id="salePriceContainer" class="mb-3" style="display:none;">
                    <label for="carSalePrice" class="form-label">Sale Price ($)</label>
                    <input type="number" class="form-control" id="carSalePrice" min="1">
                </div>
                <div class="mb-3">
                    <label for="carImage" class="form-label">Car Image</label>
                    <input type="file" class="form-control" id="carImage" accept="image/*">
                </div>
            </form>`,
        showCancelButton: true,
        confirmButtonText: 'Save',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const name = document.getElementById('carName').value;
            const model = document.getElementById('carModel').value;
            const year = document.getElementById('carYear').value;
            const color = document.getElementById('carColor').value;
            const plate = document.getElementById('carPlate').value;
            const status = document.getElementById('carStatus').value;
            const dailyPrice = document.getElementById('carDailyPrice').value;
            const salePrice = document.getElementById('carSalePrice').value;
            
            if (!name || !model || !year || !color || !plate || !status) {
                Swal.showValidationMessage('Please fill all required fields');
                return false;
            }
            if ((status === 'Available' || status === 'Reserved') && !dailyPrice) {
                Swal.showValidationMessage('Please enter daily price');
                return false;
            }
            if (status === 'Sold' && !salePrice) {
                Swal.showValidationMessage('Please enter sale price');
                return false;
            }
            
            return { 
                name, 
                model, 
                year, 
                color, 
                plate, 
                status, 
                dailyPrice, 
                salePrice 
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const priceDisplay = result.value.status === 'Sold' ? 
                `$${result.value.salePrice}` : 
                `$${result.value.dailyPrice}/day`;
            
            Swal.fire({
                title: 'Success!',
                text: 'Car added successfully',
                icon: 'success'
            });
            
            const newCarId = 'CAR' + (Math.floor(Math.random() * 9000) + 1000);
            const carsTable = $('#carsTable').DataTable();
            carsTable.row.add([
                newCarId,
                result.value.name,
                result.value.model,
                result.value.year,
                result.value.color,
                result.value.plate,
                `<span class="status-badge ${getStatusClass(result.value.status)}">${result.value.status}</span>`,
                priceDisplay,
                `<button class="btn btn-info btn-sm" onclick="viewCarDetails('${newCarId}')">
                    <i class="fas fa-eye"></i> View</button>`
            ]).draw();
        }
    });
}

function showDeleteCarModal() {
    Swal.fire({
        title: 'Delete Car',
        html: `
            <form id="deleteCarForm">
                <div class="mb-3">
                    <label for="deleteCarId" class="form-label">Car ID</label>
                    <input type="text" class="form-control" id="deleteCarId" placeholder="Enter car ID" required>
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-secondary" onclick="searchCarToDelete()">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                <div id="carToDeleteInfo" style="display:none;">
                    <hr>
                    <h5>Car Information</h5>
                    <p><strong>Name:</strong> <span id="deleteCarName"></span></p>
                    <p><strong>Model:</strong> <span id="deleteCarModel"></span></p>
                    <p><strong>Color:</strong> <span id="deleteCarColor"></span></p>
                    <p><strong>Status:</strong> <span id="deleteCarStatus"></span></p>
                </div>
            </form>`,
        showCancelButton: true,
        confirmButtonText: 'Confirm Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#d33',
        preConfirm: () => {
            const carId = document.getElementById('deleteCarId').value;
            if (!carId) {
                Swal.showValidationMessage('Please enter car ID');
                return false;
            }
            return carId;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire(
                'Deleted!',
                'Car deleted successfully.',
                'success'
            );
        }
    });
}

function searchCarToDelete() {
    const carId = document.getElementById('deleteCarId').value;
    if (!carId) {
        Swal.showValidationMessage('Please enter car ID');
        return;
    }
    
    const carInfo = {
        name: 'Toyota Camry',
        model: 'Camry',
        color: 'White',
        status: 'Available'
    };
    
    document.getElementById('deleteCarName').textContent = carInfo.name;
    document.getElementById('deleteCarModel').textContent = carInfo.model;
    document.getElementById('deleteCarColor').textContent = carInfo.color;
    document.getElementById('deleteCarStatus').textContent = carInfo.status;
    document.getElementById('carToDeleteInfo').style.display = 'block';
}

function showUpdateCarModal() {
    Swal.fire({
        title: 'Update Car Information',
        html: `
            <form id="updateCarForm">
                <div class="mb-3">
                    <label for="updateCarId" class="form-label">Car ID</label>
                    <input type="text" class="form-control" id="updateCarId" placeholder="Enter car ID" required>
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-secondary" onclick="searchCarToUpdate()">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                <div id="carToUpdateInfo" style="display:none;">
                    <hr>
                    <div class="mb-3">
                        <label for="updateCarName" class="form-label">Car Name</label>
                        <input type="text" class="form-control" id="updateCarName" required>
                    </div>
                    <div class="mb-3">
                        <label for="updateCarModel" class="form-label">Model</label>
                        <input type="text" class="form-control" id="updateCarModel" required>
                    </div>
                    <div class="mb-3">
                        <label for="updateCarYear" class="form-label">Year</label>
                        <input type="number" class="form-control" id="updateCarYear" min="2000" max="2023" required>
                    </div>
                    <div class="mb-3">
                        <label for="updateCarColor" class="form-label">Color</label>
                        <input type="text" class="form-control" id="updateCarColor" required>
                    </div>
                    <div class="mb-3">
                        <label for="updateCarPlate" class="form-label">License Plate</label>
                        <input type="text" class="form-control" id="updateCarPlate" required>
                    </div>
                    <div class="mb-3">
                        <label for="updateCarStatus" class="form-label">Status</label>
                        <select class="form-select" id="updateCarStatus" onchange="updatePriceFieldInUpdate()" required>
                            <option value="Available">Available</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Sold">Sold</option>
                            <option value="Out of Service">Out of Service</option>
                        </select>
                    </div>
                    <div id="updateDailyPriceContainer" class="mb-3">
                        <label for="updateCarDailyPrice" class="form-label">Daily Price ($)</label>
                        <input type="number" class="form-control" id="updateCarDailyPrice" min="1">
                    </div>
                    <div id="updateSalePriceContainer" class="mb-3" style="display:none;">
                        <label for="updateCarSalePrice" class="form-label">Sale Price ($)</label>
                        <input type="number" class="form-control" id="updateCarSalePrice" min="1">
                    </div>
                    <div class="mb-3">
                        <label for="updateCarImage" class="form-label">Change Car Image</label>
                        <input type="file" class="form-control" id="updateCarImage" accept="image/*">
                    </div>
                </div>
            </form>`,
        showCancelButton: true,
        confirmButtonText: 'Save Changes',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const carId = document.getElementById('updateCarId').value;
            const name = document.getElementById('updateCarName').value;
            const model = document.getElementById('updateCarModel').value;
            const year = document.getElementById('updateCarYear').value;
            const color = document.getElementById('updateCarColor').value;
            const plate = document.getElementById('updateCarPlate').value;
            const status = document.getElementById('updateCarStatus').value;
            const dailyPrice = document.getElementById('updateCarDailyPrice').value;
            const salePrice = document.getElementById('updateCarSalePrice').value;
            
            if (!carId || !name || !model || !year || !color || !plate || !status) {
                Swal.showValidationMessage('Please fill all required fields');
                return false;
            }
            if ((status === 'Available' || status === 'Reserved') && !dailyPrice) {
                Swal.showValidationMessage('Please enter daily price');
                return false;
            }
            if (status === 'Sold' && !salePrice) {
                Swal.showValidationMessage('Please enter sale price');
                return false;
            }
            
            return { 
                carId, 
                name, 
                model, 
                year, 
                color, 
                plate, 
                status, 
                dailyPrice, 
                salePrice 
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Updated!',
                text: 'Car information updated successfully',
                icon: 'success'
            });
        }
    });
}

function updatePriceFieldInUpdate() {
    const status = document.getElementById('updateCarStatus').value;
    const dailyPriceContainer = document.getElementById('updateDailyPriceContainer');
    const salePriceContainer = document.getElementById('updateSalePriceContainer');
    
    dailyPriceContainer.style.display = 'none';
    salePriceContainer.style.display = 'none';
    
    if (status === 'Available' || status === 'Reserved') {
        dailyPriceContainer.style.display = 'block';
    } else if (status === 'Sold') {
        salePriceContainer.style.display = 'block';
    }
}

function searchCarToUpdate() {
    const carId = document.getElementById('updateCarId').value;
    if (!carId) {
        Swal.showValidationMessage('Please enter car ID');
        return;
    }
    
    const carInfo = {
        name: 'Toyota Camry',
        model: 'Camry',
        year: '2022',
        color: 'White',
        plate: 'ABC 1234',
        status: 'Available',
        dailyPrice: '120'
    };
    
    document.getElementById('updateCarName').value = carInfo.name;
    document.getElementById('updateCarModel').value = carInfo.model;
    document.getElementById('updateCarYear').value = carInfo.year;
    document.getElementById('updateCarColor').value = carInfo.color;
    document.getElementById('updateCarPlate').value = carInfo.plate;
    document.getElementById('updateCarStatus').value = carInfo.status;
    document.getElementById('updateCarDailyPrice').value = carInfo.dailyPrice;
    updatePriceFieldInUpdate();
    document.getElementById('carToUpdateInfo').style.display = 'block';
}

function showChangeStatusModal() {
    Swal.fire({
        title: 'Change Car Status',
        html: `
            <form id="changeStatusForm">
                <div class="mb-3">
                    <label for="statusCarId" class="form-label">Car ID</label>
                    <input type="text" class="form-control" id="statusCarId" placeholder="Enter car ID" required>
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-secondary" onclick="searchCarToChangeStatus()">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                <div id="carToChangeStatusInfo" style="display:none;">
                    <hr>
                    <h5>Car Information</h5>
                    <p><strong>Name:</strong> <span id="statusCarName"></span></p>
                    <p><strong>Model:</strong> <span id="statusCarModel"></span></p>
                    <p><strong>Current Status:</strong> <span id="currentCarStatus"></span></p>
                    <div class="mb-3 mt-3">
                        <label for="newCarStatus" class="form-label">New Status</label>
                        <select class="form-select" id="newCarStatus" onchange="updatePriceFieldInStatusChange()" required>
                            <option value="Available">Available</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Sold">Sold</option>
                            <option value="Out of Service">Out of Service</option>
                        </select>
                    </div>
                    <div id="statusDailyPriceContainer" class="mb-3">
                        <label for="statusCarDailyPrice" class="form-label">Daily Price ($)</label>
                        <input type="number" class="form-control" id="statusCarDailyPrice" min="1">
                    </div>
                    <div id="statusSalePriceContainer" class="mb-3" style="display:none;">
                        <label for="statusCarSalePrice" class="form-label">Sale Price ($)</label>
                        <input type="number" class="form-control" id="statusCarSalePrice" min="1">
                    </div>
                </div>
            </form>`,
        showCancelButton: true,
        confirmButtonText: 'Change Status',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const carId = document.getElementById('statusCarId').value;
            const newStatus = document.getElementById('newCarStatus').value;
            const dailyPrice = document.getElementById('statusCarDailyPrice').value;
            const salePrice = document.getElementById('statusCarSalePrice').value;
            
            if (!carId || !newStatus) {
                Swal.showValidationMessage('Please fill all required fields');
                return false;
            }
            if ((newStatus === 'Available' || newStatus === 'Reserved') && !dailyPrice) {
                Swal.showValidationMessage('Please enter daily price');
                return false;
            }
            if (newStatus === 'Sold' && !salePrice) {
                Swal.showValidationMessage('Please enter sale price');
                return false;
            }
            
            return { carId, newStatus, dailyPrice, salePrice };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Changed!',
                text: 'Car status changed successfully',
                icon: 'success'
            });
        }
    });
}

function updatePriceFieldInStatusChange() {
    const status = document.getElementById('newCarStatus').value;
    const dailyPriceContainer = document.getElementById('statusDailyPriceContainer');
    const salePriceContainer = document.getElementById('statusSalePriceContainer');
    
    dailyPriceContainer.style.display = 'none';
    salePriceContainer.style.display = 'none';
    
    if (status === 'Available' || status === 'Reserved') {
        dailyPriceContainer.style.display = 'block';
    } else if (status === 'Sold') {
        salePriceContainer.style.display = 'block';
    }
}

function searchCarToChangeStatus() {
    const carId = document.getElementById('statusCarId').value;
    if (!carId) {
        Swal.showValidationMessage('Please enter car ID');
        return;
    }
    
    const carInfo = {
        name: 'Toyota Camry',
        model: 'Camry',
        status: 'Available',
        dailyPrice: '120'
    };
    
    document.getElementById('statusCarName').textContent = carInfo.name;
    document.getElementById('statusCarModel').textContent = carInfo.model;
    document.getElementById('currentCarStatus').textContent = carInfo.status;
    document.getElementById('statusCarDailyPrice').value = carInfo.dailyPrice;
    document.getElementById('newCarStatus').value = carInfo.status;
    updatePriceFieldInStatusChange();
    document.getElementById('carToChangeStatusInfo').style.display = 'block';
}

function viewCarDetails(carId) {
    let carInfo;
    
    // Find car information based on carId
    $('#carsTable tbody tr').each(function() {
        if ($(this).find('td:first').text() === carId) {
            carInfo = {
                id: carId,
                name: $(this).find('td:nth-child(2)').text(),
                model: $(this).find('td:nth-child(3)').text(),
                year: $(this).find('td:nth-child(4)').text(),
                color: $(this).find('td:nth-child(5)').text(),
                plate: $(this).find('td:nth-child(6)').text(),
                status: $(this).find('td:nth-child(7) span').text(),
                price: $(this).find('td:nth-child(8)').text()
            };
            return false; // Stops the each loop
        }
    });

    Swal.fire({
        title: 'Car Details',
        html: `
            <div class="row">
                <div class="col-md-6">
                    <img src="https://via.placeholder.com/300" class="img-fluid mb-3" alt="Car Image">
                </div>
                <div class="col-md-6">
                    <h4>${carInfo.name} ${carInfo.year}</h4>
                    <p><strong>Car ID:</strong> ${carInfo.id}</p>
                    <p><strong>Name:</strong> ${carInfo.name}</p>
                    <p><strong>Model:</strong> ${carInfo.model}</p>
                    <p><strong>Year:</strong> ${carInfo.year}</p>
                    <p><strong>Color:</strong> ${carInfo.color}</p>
                    <p><strong>License Plate:</strong> ${carInfo.plate}</p>
                    <p><strong>Status:</strong> <span class="status-badge ${getStatusClass(carInfo.status)}">${carInfo.status}</span></p>
                    <p><strong>Price:</strong> ${carInfo.price}</p>
                </div>
            </div>`,
        width: '800px',
        showConfirmButton: false,
        showCloseButton: true
    });
}

function getStatusClass(status) {
    switch(status) {
        case 'Available': return 'available';
        case 'Reserved': return 'reserved';
        case 'Sold': return 'sold';
        case 'Purchased': return 'purchased';
        case 'Out of Service': return 'out-of-service';
        default: return '';
    }
}

// Filter table by car status
$(document).on('click', '.filter-option', function(e) {
    e.preventDefault();
    const filterValue = $(this).data('filter');

    if (filterValue === 'all') {
        $('#carsTable').DataTable().column(6).search('').draw();
    } else {
        $('#carsTable').DataTable().column(6).search(filterValue).draw();
    }

    // Update button text to reflect current filter
    $('#filterDropdown').html(`<i class="fas fa-filter"></i> ${filterValue === 'all' ? 'Filter by Status' : 'Status: ' + filterValue}`);
});

// Helper function to update car status in table
function updateCarStatusInTable(carId, newStatus) {
    const table = $('#carsTable').DataTable();
    const row = table.rows().nodes().to$().find(`td:first-child:contains('${carId}')`).closest('tr');

    if (row.length) {
        const statusCell = row.find('td:nth-child(7)');
        statusCell.html(`<span class="status-badge ${getStatusClass(newStatus)}">${newStatus}</span>`);
        
        // Update price if needed
        const priceCell = row.find('td:nth-child(8)');
        if (newStatus === 'Sold') {
            priceCell.text('$15,000'); // You can make this dynamic
        } else if (newStatus === 'Available' || newStatus === 'Reserved') {
            priceCell.text('$120/day'); // You can make this dynamic
        } else {
            priceCell.text('-');
        }
    }
}