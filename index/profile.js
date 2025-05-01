// Adding functionality for the buttons

const editProfileButton = document.getElementById('edit-profile');
const logoutButton = document.getElementById('logout');
const homeButton = document.getElementById('home-button');

// Redirect to the edit profile page
editProfileButton.addEventListener('click', function() {
    window.location.href = 'edit-profile.html'; // تأكد من مسار صفحة تعديل الملف الشخصي
});

// Logout functionality (for now it will just alert the user)
logoutButton.addEventListener('click', function() {
    alert('You have logged out!');
    window.location.href = 'login.html'; // تأكد من مسار صفحة تسجيل الدخول
});

// Redirect to the home page
homeButton.addEventListener('click', function() {
    window.location.href = 'index.html'; // تأكد من مسار الصفحة الرئيسية
});
