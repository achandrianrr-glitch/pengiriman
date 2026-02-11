(function () {
  const profileBtn = document.getElementById("profileBtn");
  const profileMenu = document.getElementById("profileMenu");

  function openMenu() {
    if (!profileMenu || !profileBtn) return;
    profileMenu.classList.add("open");
    profileBtn.setAttribute("aria-expanded", "true");
  }

  function closeMenu() {
    if (!profileMenu || !profileBtn) return;
    profileMenu.classList.remove("open");
    profileBtn.setAttribute("aria-expanded", "false");
  }

  if (profileBtn && profileMenu) {
    profileBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      const isOpen = profileMenu.classList.contains("open");
      if (isOpen) closeMenu();
      else openMenu();
    });

    document.addEventListener("click", () => closeMenu());

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeMenu();
    });
  }
})();
