(function () {
  const form = document.getElementById("loginForm");
  const email = document.getElementById("email");
  const password = document.getElementById("password");
  const toggleBtn = document.getElementById("togglePassword");
  const toggleIcon = document.getElementById("toggleIcon");
  const submitBtn = document.getElementById("submitBtn");
  const btnText = document.getElementById("btnText");
  const btnSpinner = document.getElementById("btnSpinner");

  const errEmail = document.getElementById("error-email");
  const errPass = document.getElementById("error-password");

  function setInvalid(input, errEl, message) {
    input.classList.add("invalid");
    if (errEl) {
      errEl.textContent = message;
      errEl.classList.remove("hidden");
    }
  }

  function setValid(input, errEl) {
    input.classList.remove("invalid");
    if (errEl) errEl.classList.add("hidden");
  }

  function isGmail(emailStr) {
    return emailStr.toLowerCase().includes("@gmail.com");
  }

  function validate() {
    let ok = true;

    const e = (email.value || "").trim();
    const p = password.value || "";

    if (!e) {
      ok = false;
      setInvalid(email, errEmail, "Email wajib diisi");
    } else {
      const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!pattern.test(e)) {
        ok = false;
        setInvalid(email, errEmail, "Email tidak valid");
      } else if (!isGmail(e)) {
        ok = false;
        setInvalid(email, errEmail, "Email harus @gmail.com");
      } else {
        setValid(email, errEmail);
      }
    }

    if (!p) {
      ok = false;
      setInvalid(password, errPass, "Password wajib diisi");
    } else if (p.length < 8) {
      ok = false;
      setInvalid(password, errPass, "Password minimal 8 karakter");
    } else {
      setValid(password, errPass);
    }

    return ok;
  }

  if (toggleBtn && toggleIcon) {
    toggleBtn.addEventListener("click", function () {
      const isHidden = password.type === "password";
      password.type = isHidden ? "text" : "password";
      toggleIcon.classList.toggle("bi-eye");
      toggleIcon.classList.toggle("bi-eye-slash");
      toggleBtn.setAttribute(
        "aria-label",
        isHidden ? "Sembunyikan password" : "Tampilkan password",
      );
    });
  }

  email.addEventListener("input", () => setValid(email, errEmail));
  password.addEventListener("input", () => setValid(password, errPass));

  form.addEventListener("submit", function (e) {
    if (!validate()) {
      e.preventDefault();
      return;
    }

    submitBtn.disabled = true;
    btnText.textContent = "Memproses...";
    btnSpinner.classList.remove("hidden");
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
      const active = document.activeElement;
      if (active === email || active === password) {
        // biarkan submit normal oleh browser
      }
    }
  });
})();
