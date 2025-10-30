document.addEventListener("DOMContentLoaded", () => {
  const showLoginBtn = document.getElementById("show-login-btn");
  const showSignupBtn = document.getElementById("show-signup-btn");
  const confirmField = document.getElementById("confirm-field");
  const confirmInput = document.getElementById("confirm");
  const signupBtn = document.getElementById("signup-btn");
  const loginBtn = document.getElementById("login-btn");

  function setActive(btn) {
    showLoginBtn.classList.remove("active");
    showSignupBtn.classList.remove("active");
    btn.classList.add("active");
  }

  showSignupBtn.addEventListener("click", function () {
    confirmField.style.display = "block";
    confirmInput.setAttribute("required", "");
    signupBtn.style.display = "block";
    loginBtn.style.display = "none";
    setActive(showSignupBtn);
  });

  showLoginBtn.addEventListener("click", function () {
    confirmField.style.display = "none";
    confirmInput.removeAttribute("required");
    signupBtn.style.display = "none";
    loginBtn.style.display = "block";
    setActive(showLoginBtn);
  });

  setActive(showLoginBtn);
});
