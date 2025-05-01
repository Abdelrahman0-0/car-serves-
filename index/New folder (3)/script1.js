function validateForm() {
    const input = document.getElementById("identifier").value.trim();
    const error = document.getElementById("error");

    if (input === "") {
        error.textContent = "Please enter your email, phone, or username.";
        return false;
    }

    // Basic pattern check (not strict, just to help)
    const isEmail = /\S+@\S+\.\S+/.test(input);
    const isPhone = /^[0-9]{8,15}$/.test(input);
    const isUsername = /^[a-zA-Z0-9_]{3,20}$/.test(input);

    if (!isEmail && !isPhone && !isUsername) {
        error.textContent = "Please enter a valid email, phone number, or username.";
        return false;
    }

    error.textContent = "";
    return true;
}