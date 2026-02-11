(function () {
  const KEY = "darkMode";
  const html = document.documentElement;

  function setIcon(isDark) {
    const btn = document.getElementById("dark-mode-toggle");
    if (!btn) return;
    const icon = btn.querySelector("i");
    if (!icon) return;

    icon.classList.remove("bi-moon", "bi-sun");
    icon.classList.add(isDark ? "bi-sun" : "bi-moon");
  }

  function applyModeFromStorage() {
    const enabled = localStorage.getItem(KEY) === "enabled";
    if (enabled) html.classList.add("dark");
    else html.classList.remove("dark");
    setIcon(enabled);
  }

  function dispatchChange() {
    document.dispatchEvent(
      new CustomEvent("darkmode:changed", {
        detail: { enabled: html.classList.contains("dark") },
      }),
    );
  }

  document.addEventListener("DOMContentLoaded", () => {
    // Pastikan apply untuk SEMUA halaman
    applyModeFromStorage();

    const toggle = document.getElementById("dark-mode-toggle");
    if (!toggle) return;

    toggle.addEventListener("click", () => {
      const isDark = html.classList.toggle("dark");
      localStorage.setItem(KEY, isDark ? "enabled" : "disabled");
      setIcon(isDark);
      dispatchChange();
    });
  });

  // Jika user punya banyak tab, sinkron
  window.addEventListener("storage", (e) => {
    if (e.key === KEY) {
      applyModeFromStorage();
      dispatchChange();
    }
  });
})();
