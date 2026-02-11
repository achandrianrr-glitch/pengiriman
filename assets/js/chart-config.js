(function () {
  if (typeof Chart === "undefined") return;
  if (!window.__dashboardData) return;

  function cssVar(name, fallback) {
    const val = getComputedStyle(document.documentElement)
      .getPropertyValue(name)
      .trim();
    return val || fallback;
  }

  function sliceLast(arr, n) {
    if (!Array.isArray(arr)) return [];
    return arr.slice(Math.max(arr.length - n, 0));
  }

  function buildRevenue(days) {
    const labels365 = window.__dashboardData.revenue.labels365 || [];
    const data365 = window.__dashboardData.revenue.data365 || [];
    return {
      labels: sliceLast(labels365, days),
      data: sliceLast(data365, days),
    };
  }

  Chart.defaults.font.family = "Plus Jakarta Sans, sans-serif";

  let chartPendapatan = null;

  function applyThemeToCharts() {
    const textSecondary = cssVar("--text-secondary", "#6B7280");
    const grid = cssVar("--border", "#E5E7EB");

    Chart.defaults.color = textSecondary;

    if (chartPendapatan) {
      chartPendapatan.options.scales.x.grid.color = grid;
      chartPendapatan.options.scales.y.grid.color = grid;
      chartPendapatan.update();
    }
  }

  // Revenue Chart
  const revCanvas = document.getElementById("chartPendapatan");
  if (revCanvas) {
    const initial = buildRevenue(30);

    chartPendapatan = new Chart(revCanvas, {
      type: "line",
      data: {
        labels: initial.labels,
        datasets: [
          {
            label: "Pendapatan (Rp)",
            data: initial.data,
            borderColor: "#6B9FD4",
            backgroundColor: "rgba(107, 159, 212, 0.14)",
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 2,
            pointHoverRadius: 4,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) =>
                "Rp " + Number(ctx.parsed.y || 0).toLocaleString("id-ID"),
            },
          },
        },
        scales: {
          x: { grid: { color: cssVar("--border", "#E5E7EB") } },
          y: {
            beginAtZero: true,
            grid: { color: cssVar("--border", "#E5E7EB") },
            ticks: {
              callback: (v) => "Rp " + Number(v || 0).toLocaleString("id-ID"),
            },
          },
        },
      },
    });

    const filter = document.getElementById("revenueFilter");
    if (filter) {
      filter.addEventListener("change", () => {
        const days = Number(filter.value || 30);
        const next = buildRevenue(days);
        chartPendapatan.data.labels = next.labels;
        chartPendapatan.data.datasets[0].data = next.data;
        chartPendapatan.update();
      });
    }

    const btn = document.getElementById("downloadRevenuePng");
    if (btn) {
      btn.addEventListener("click", () => {
        const url = chartPendapatan.toBase64Image("image/png", 1);
        const a = document.createElement("a");
        a.href = url;
        a.download = "pendapatan_dashboard.png";
        document.body.appendChild(a);
        a.click();
        a.remove();
      });
    }
  }

  // Donut Status
  const statusCanvas = document.getElementById("chartStatus");
  if (statusCanvas) {
    const labels = window.__dashboardData.status.labels || [];
    const data = window.__dashboardData.status.data || [];

    function statusColor(label) {
      const s = String(label || "")
        .toLowerCase()
        .trim();
      if (s === "diproses") return "#F59E0B";
      if (s === "dalam perjalanan") return "#3B82F6";
      if (s === "sampai tujuan") return "#6366F1";
      if (s === "selesai") return "#10B981";
      if (s === "dibatalkan") return "#EF4444";
      if (s === "terlambat") return "#991B1B";
      return "#9CA3AF";
    }

    new Chart(statusCanvas, {
      type: "doughnut",
      data: {
        labels,
        datasets: [
          {
            data,
            backgroundColor: labels.map(statusColor),
            borderWidth: 0,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: "right" },
          tooltip: {
            callbacks: { label: (ctx) => `${ctx.label}: ${ctx.parsed} paket` },
          },
        },
      },
    });
  }

  // Mini trend
  const mini = document.getElementById("chartTransitMini");
  if (mini) {
    const labels = window.__dashboardData.transitMini.labels || [];
    const data = window.__dashboardData.transitMini.data || [];

    new Chart(mini, {
      type: "line",
      data: {
        labels,
        datasets: [
          {
            data,
            borderColor: "#3B82F6",
            backgroundColor: "rgba(59, 130, 246, 0.12)",
            borderWidth: 2,
            tension: 0.4,
            pointRadius: 0,
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: { x: { display: false }, y: { display: false } },
      },
    });
  }

  // Apply theme on load and when darkmode toggled
  document.addEventListener("DOMContentLoaded", applyThemeToCharts);
  document.addEventListener("darkmode:changed", applyThemeToCharts);
})();
