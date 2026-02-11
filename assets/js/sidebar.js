(function () {
  document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.querySelector(".sidebar");
    const toggle = document.getElementById("sidebar-toggle");
    let backdrop = null;

    function openSidebar() {
      if (!sidebar) return;
      sidebar.classList.add("open");

      if (!backdrop) {
        backdrop = document.createElement("div");
        backdrop.className = "sidebar-backdrop";
        document.body.appendChild(backdrop);
        backdrop.addEventListener("click", closeSidebar);
      }
    }

    function closeSidebar() {
      if (!sidebar) return;
      sidebar.classList.remove("open");
      if (backdrop) {
        backdrop.remove();
        backdrop = null;
      }
    }

    if (toggle) {
      toggle.addEventListener("click", () => {
        if (!sidebar) return;
        sidebar.classList.contains("open") ? closeSidebar() : openSidebar();
      });
    }

    // Dropdown toggle
    document.querySelectorAll(".menu-dropdown").forEach((wrap) => {
      const btn = wrap.querySelector(".sidebar-menu-button");
      const submenu = wrap.querySelector(".submenu");
      if (!btn || !submenu) return;

      btn.addEventListener("click", () => {
        const isOpen = submenu.classList.toggle("open");
        btn.classList.toggle("open", isOpen);
      });
    });

    // Close sidebar on ESC (mobile)
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeSidebar();
    });
  });
})();
