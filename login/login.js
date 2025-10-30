document.addEventListener('DOMContentLoaded', () => {
  const showLoginBtn = document.getElementById('show-login-btn');
  const showSignupBtn = document.getElementById('show-signup-btn');
  const confirmPass = document.getElementById('confirm-field');
  const signupBtn = document.getElementById('signup-btn');
  const loginBtn = document.getElementById('login-btn');

  function setActive(btn) {
    showLoginBtn.classList.remove('active');
    showSignupBtn.classList.remove('active');
    btn.classList.add('active');
  }

  showSignupBtn.addEventListener('click', function () {
    confirmPass.style.display = 'block';
    signupBtn.style.display = 'block';
    loginBtn.style.display = 'none';
    setActive(showSignupBtn);
  });

  showLoginBtn.addEventListener('click', function () {
    confirmPass.style.display = 'none';
    signupBtn.style.display = 'none';
    loginBtn.style.display = 'block';
    setActive(showLoginBtn);
  });

  setActive(showLoginBtn);
});
