(function () {
  const nfNumber = new Intl.NumberFormat("id-ID");
  const nfCurrency = new Intl.NumberFormat("id-ID");

  function render(el, value) {
    if (el.classList.contains("currency")) {
      el.textContent = "Rp " + nfCurrency.format(Math.floor(value));
    } else {
      el.textContent = nfNumber.format(Math.floor(value));
    }
  }

  function animate(el, end, duration = 1200) {
    const start = 0;
    const startTime = performance.now();

    function easeOutCubic(t) {
      return 1 - Math.pow(1 - t, 3);
    }

    function frame(now) {
      const p = Math.min((now - startTime) / duration, 1);
      const eased = easeOutCubic(p);
      const current = start + (end - start) * eased;
      render(el, current);

      if (p < 1) requestAnimationFrame(frame);
      else render(el, end);
    }

    requestAnimationFrame(frame);
  }

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-countup]").forEach((el) => {
      const end = Number(el.getAttribute("data-countup") || 0);
      animate(el, isNaN(end) ? 0 : end, 1300); // halus & cepat
    });
  });
})();
