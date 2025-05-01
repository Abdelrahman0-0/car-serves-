function openDetailsWindow(carName, colors, colorImages) {
    const colorArray = colors.split(',').map(color => color.trim());
    const imageArray = colorImages.split(',').map(img => img.trim());

    const win = window.open('', '_blank', 'width=600,height=400');
    let content = `
      <html>
        <head>
          <title>${carName} Details</title>
          <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f9f9f9; }
            h2 { color: #333; }
            .colors { display: flex; flex-wrap: wrap; gap: 10px; }
            .color-item { text-align: center; }
            .color-item img { width: 100px; height: 60px; border-radius: 8px; }
            .color-item p { margin-top: 5px; color: #444; }
          </style>
        </head>
        <body>
          <h2>${carName}</h2>
          <div class="colors">`;

    for (let i = 0; i < colorArray.length; i++) {
        content += `
        <div class="color-item">
          <img src="images/${imageArray[i]}" alt="${colorArray[i]}" />
          <p>${colorArray[i]}</p>
        </div>
      `;
    }

    content += `
          </div>
        </body>
      </html>
    `;

    win.document.write(content);
}

function searchCars() {
    const input = document.getElementById("searchInput").value.toLowerCase();

    const container = document.querySelectorAll(".car-container");
    container.forEach(c => c.innerHTML = ""); // نفضيهم كلهم مؤقتًا

    const cars = [
        { name: "Toyota Corolla", type: "new", price: "EGP 550,000", colors: "Red, Black, Silver", images: "red.jpg, black.jpg, silver.jpg" },
        { name: "Mazda 3", type: "new", price: "EGP 580,000", colors: "White, Blue, Gray", images: "white.jpg, blue.jpg, gray.jpg" },
        { name: "Hyundai Elantra", type: "new", price: "EGP 600,000", colors: "Black, Silver, Bronze", images: "black.jpg, silver.jpg, bronze.jpg" },
        { name: "Honda Civic", type: "rent", price: "EGP 1,200 / day", colors: "Gray, Black", images: "gray.jpg, black.jpg" },
        { name: "Renault Logan", type: "rent", price: "EGP 950 / day", colors: "Silver, Blue", images: "silver.jpg, blue.jpg" },
        { name: "Kia Cerato", type: "used", price: "EGP 330,000", colors: "Gray", images: "gray.jpg" },
        { name: "Nissan Sunny", type: "used", price: "EGP 280,000", colors: "White, Silver", images: "white.jpg, silver.jpg" }
    ];

    const filteredCars = cars.filter(car => car.name.toLowerCase().includes(input));

    filteredCars.forEach(car => {
        const section = document.querySelector(`.section:nth-child(${getSectionIndex(car.type)}) .car-container`);
        if (section) {
            section.innerHTML += `
          <div class="car-card" data-name="${car.name}">
            <img src="https://via.placeholder.com/250x150?text=${car.name}" alt="${car.name}">
            <h3>${car.name}</h3>
            <p>${car.type === 'rent' ? 'Rent: ' : 'Price: '}${car.price}</p>
            <button onclick="openDetailsWindow('${car.name}', '${car.colors}', '${car.images}')">See All</button>
          </div>
        `;
        }
    });
}

function getSectionIndex(type) {
    switch (type) {
        case 'new':
            return 2;
        case 'rent':
            return 4;
        case 'used':
            return 6;
        default:
            return 2;
    }
}