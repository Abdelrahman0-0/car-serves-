// script.js
document.getElementById('car-registration-form').addEventListener('submit', function(event) {
    event.preventDefault();
    alert("Car registered successfully!");
});
// script.js
document.getElementById('car-reservation-form').addEventListener('submit', function(event) {
    event.preventDefault();
    alert("Car reserved successfully!");
});
// script.js
document.getElementById('customer-registration-form').addEventListener('submit', function(event) {
    event.preventDefault();
    alert("Account created successfully!");
});
document.getElementById("loginForm").addEventListener("submit", function(event) {
    event.preventDefault(); // منع التحديث الافتراضي للصفحة

    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();
    const message = document.getElementById("message");

    // التحقق من بيانات تسجيل الدخول
    if (username === "admin" && password === "1234") {
        message.style.color = "green";
        message.textContent = "تم تسجيل الدخول بنجاح!";
        // إعادة التوجيه إلى صفحة أخرى (اختياري)
        setTimeout(() => {
            window.location.href = "dashboard.html"; // قم بإنشاء هذه الصفحة لاحقًا
        }, 2000);
    } else {
        message.style.color = "red";
        message.textContent = "اسم المستخدم أو كلمة المرور غير صحيحة!";
    }
});


