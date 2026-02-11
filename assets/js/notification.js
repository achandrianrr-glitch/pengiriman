function showNotification(message, type = "success") {
  const root = document.getElementById("toast-root");
  if (!root) return;

  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;

  let icon = "info-circle";
  if (type === "success") icon = "check-circle";
  if (type === "error") icon = "exclamation-triangle";
  if (type === "warning") icon = "exclamation-circle";

  toast.innerHTML = `
        <i class="bi bi-${icon}"></i>
        <span></span>
    `;

  toast.querySelector("span").textContent = message;

  root.appendChild(toast);

  requestAnimationFrame(() => toast.classList.add("show"));

  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => toast.remove(), 250);
  }, 3000);
}
