const body = document.body;

// === Top Section ===
const topSection = document.createElement('div');
topSection.className = 'section';

const usernameInput = document.createElement('input');
usernameInput.type = 'text';
usernameInput.placeholder = 'Enter your username';

const loginBtn = document.createElement('button');
loginBtn.textContent = 'Login';

topSection.appendChild(usernameInput);
topSection.appendChild(loginBtn);
body.appendChild(topSection);

// === Bottom Section ===
const bottomSection = document.createElement('div');
bottomSection.className = 'section';

const buttonGroup = document.createElement('div');
buttonGroup.className = 'button-group';

const menuBtn = document.createElement('button');
menuBtn.textContent = 'Show/Hide Menu';

const detailsBtn = document.createElement('button');
detailsBtn.textContent = 'Show Details';

buttonGroup.appendChild(menuBtn);
buttonGroup.appendChild(detailsBtn);
bottomSection.appendChild(buttonGroup);
body.appendChild(bottomSection);

// === Menu and Details ===
const navMenu = document.createElement('div');
navMenu.className = 'toggle-box';
navMenu.innerHTML = `
<p>🏠 Home</p>
<p>📄 About</p>
<p>📞 Contact</p>
`;

const details = document.createElement('div');
details.className = 'toggle-box';
details.innerHTML = <p>Here are some additional details that appear when you click the button.</p>;

body.appendChild(navMenu);
body.appendChild(details);

// === Popup ===
const popup = document.createElement('div');
popup.className = 'popup';

const popupText = document.createElement('h3');
popup.appendChild(popupText);

const popupClose = document.createElement('button');
popupClose.textContent = 'Close';
popupClose.onclick = () => {
    popup.classList.remove('show');
};
popup.appendChild(popupClose);

body.appendChild(popup);

// === Events ===
menuBtn.onclick = () => {
    navMenu.classList.toggle('show');
};

detailsBtn.onclick = () => {
    details.classList.toggle('show');
};

loginBtn.onclick = () => {
    const username = usernameInput.value.trim();
    if (username) {
popupText.textContent = `Welcome, ${username}!`;
popupText.style.color = '#388E3C';
} else {
        popupText.textContent = 'Please enter your username.';
        popupText.style.color = 'crimson';
    }
    popup.classList.add('show');
};