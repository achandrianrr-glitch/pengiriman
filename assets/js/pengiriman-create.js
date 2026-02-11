(function () {
  "use strict";

  const form = document.getElementById("formPengiriman");
  if (!form) return;

  const el = (id) => document.getElementById(id);

  const inputAsal = el("kota_asal");
  const inputTujuan = el("kota_tujuan");
  const inputLayanan = el("jenis_layanan");
  const inputBerat = el("barang_berat_kg");
  const inputPanjang = el("barang_panjang_cm");
  const inputLebar = el("barang_lebar_cm");
  const inputTinggi = el("barang_tinggi_cm");
  const inputNilai = el("barang_nilai");
  const inputTambahan = el("biaya_tambahan");
  const inputTanggalKirim = el("tanggal_kirim");

  const chkAsuransi = el("chk_asuransi");
  const chkPacking = el("chk_packing");

  const wrapNilai = el("wrap_nilai_barang");

  const outBeratDim = el("out_berat_dim");
  const outBeratAkhir = el("out_berat_akhir");

  const outOngkir = el("out_biaya_pengiriman");
  const outAsuransi = el("out_biaya_asuransi");
  const outPacking = el("out_biaya_packing");
  const outTambahan = el("out_biaya_tambahan");
  const outTotal = el("out_total_biaya");

  const hiddenOngkir = el("hidden_biaya_pengiriman");
  const hiddenAsuransi = el("hidden_biaya_asuransi");
  const hiddenPacking = el("hidden_biaya_packing");
  const hiddenTotal = el("hidden_total_biaya");
  const hiddenEstimasi = el("estimasi_sampai");

  const btnCopy = el("btn_copy_pengirim");
  const btnSubmit = el("btn_submit");

  const loader = el("calc_loader");
  const calcMsg = el("calc_message");

  const csrfToken = el("csrf_token") ? el("csrf_token").value : "";

  let lastTotal = 0;

  function debounce(fn, wait) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  function toNumber(v) {
    const n = parseFloat(v);
    return Number.isFinite(n) ? n : 0;
  }

  function rupiah(n) {
    const x = Math.round(toNumber(n));
    return "Rp " + x.toLocaleString("id-ID");
  }

  function animateValue(element, from, to, duration, formatter) {
    const start = performance.now();
    const diff = to - from;

    function frame(now) {
      const t = Math.min(1, (now - start) / duration);
      const eased = 1 - Math.pow(1 - t, 3);
      const value = from + diff * eased;
      element.textContent = formatter(value);
      if (t < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }

  function setCalcMessage(message, type) {
    if (!calcMsg) return;
    calcMsg.textContent = message || "";
    calcMsg.className =
      "text-sm mt-2 " +
      (type === "error"
        ? "text-red-600"
        : type === "info"
        ? "text-slate-600"
        : "text-emerald-600");
  }

  function setLoading(isLoading) {
    if (!loader) return;
    loader.classList.toggle("hidden", !isLoading);
  }

  function toggleNilaiBarang() {
    if (!wrapNilai) return;
    if (chkAsuransi && chkAsuransi.checked) {
      wrapNilai.classList.remove("hidden");
    } else {
      wrapNilai.classList.add("hidden");
      if (inputNilai) inputNilai.value = "";
    }
  }

  function payload() {
    return {
      csrf_token: csrfToken,
      kota_asal: inputAsal ? inputAsal.value.trim() : "",
      kota_tujuan: inputTujuan ? inputTujuan.value.trim() : "",
      jenis_layanan: inputLayanan ? inputLayanan.value : "",
      berat_kg: inputBerat ? inputBerat.value : "",
      panjang_cm: inputPanjang ? inputPanjang.value : "",
      lebar_cm: inputLebar ? inputLebar.value : "",
      tinggi_cm: inputTinggi ? inputTinggi.value : "",
      asuransi: chkAsuransi && chkAsuransi.checked ? "1" : "0",
      packing: chkPacking && chkPacking.checked ? "1" : "0",
      nilai_barang: inputNilai ? inputNilai.value : "",
      biaya_tambahan: inputTambahan ? inputTambahan.value : "0",
      tanggal_kirim: inputTanggalKirim ? inputTanggalKirim.value : "",
    };
  }

  async function calculate() {
    setCalcMessage("", "info");

    const data = payload();

    if (!data.kota_asal || !data.kota_tujuan || !data.jenis_layanan || toNumber(data.berat_kg) <= 0) {
      if (outBeratDim) outBeratDim.textContent = "0";
      if (outBeratAkhir) outBeratAkhir.textContent = "0";
      if (outOngkir) outOngkir.textContent = rupiah(0);
      if (outAsuransi) outAsuransi.textContent = rupiah(0);
      if (outPacking) outPacking.textContent = rupiah(0);
      if (outTambahan) outTambahan.textContent = rupiah(toNumber(data.biaya_tambahan));
      if (outTotal) outTotal.textContent = rupiah(toNumber(data.biaya_tambahan));
      lastTotal = toNumber(data.biaya_tambahan);

      if (hiddenOngkir) hiddenOngkir.value = "0";
      if (hiddenAsuransi) hiddenAsuransi.value = "0";
      if (hiddenPacking) hiddenPacking.value = "0";
      if (hiddenTotal) hiddenTotal.value = String(lastTotal);

      return;
    }

    setLoading(true);

    try {
      const formBody = new URLSearchParams(data);

      const res = await fetch("pages/tarif/calculate.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: formBody.toString(),
      });

      const json = await res.json();

      if (!json.ok) {
        setCalcMessage(json.message || "Tarif tidak tersedia.", "error");
        setLoading(false);
        return;
      }

      if (outBeratDim) outBeratDim.textContent = (json.berat_dimensional || 0).toLocaleString("id-ID");
      if (outBeratAkhir) outBeratAkhir.textContent = (json.berat_akhir || 0).toLocaleString("id-ID");

      if (outOngkir) outOngkir.textContent = rupiah(json.biaya_pengiriman || 0);
      if (outAsuransi) outAsuransi.textContent = rupiah(json.biaya_asuransi || 0);
      if (outPacking) outPacking.textContent = rupiah(json.biaya_packing || 0);
      if (outTambahan) outTambahan.textContent = rupiah(json.biaya_tambahan || 0);

      const newTotal = toNumber(json.total_biaya || 0);
      if (outTotal) {
        animateValue(outTotal, lastTotal, newTotal, 450, (v) => rupiah(v));
      }
      lastTotal = newTotal;

      if (hiddenOngkir) hiddenOngkir.value = String(json.biaya_pengiriman || 0);
      if (hiddenAsuransi) hiddenAsuransi.value = String(json.biaya_asuransi || 0);
      if (hiddenPacking) hiddenPacking.value = String(json.biaya_packing || 0);
      if (hiddenTotal) hiddenTotal.value = String(json.total_biaya || 0);

      if (hiddenEstimasi && json.estimasi_sampai) hiddenEstimasi.value = json.estimasi_sampai;

      setLoading(false);
      setCalcMessage("Biaya berhasil dihitung.", "success");
    } catch (e) {
      setLoading(false);
      setCalcMessage("Gagal menghitung biaya. Coba lagi.", "error");
    }
  }

  const calculateDebounced = debounce(calculate, 250);

  function bindCalc() {
    const watch = [
      inputAsal, inputTujuan, inputLayanan, inputBerat,
      inputPanjang, inputLebar, inputTinggi,
      inputNilai, inputTambahan, inputTanggalKirim,
      chkAsuransi, chkPacking
    ].filter(Boolean);

    watch.forEach((node) => {
      node.addEventListener("input", calculateDebounced);
      node.addEventListener("change", calculateDebounced);
    });
  }

  function bindAsuransiPacking() {
    if (chkAsuransi) chkAsuransi.addEventListener("change", () => {
      toggleNilaiBarang();
      calculateDebounced();
    });
    if (chkPacking) chkPacking.addEventListener("change", calculateDebounced);
    toggleNilaiBarang();
  }

  function bindCopyPengirim() {
    if (!btnCopy) return;

    btnCopy.addEventListener("click", () => {
      const map = [
        ["pengirim_nama", "penerima_nama"],
        ["pengirim_telepon", "penerima_telepon"],
        ["pengirim_alamat", "penerima_alamat"],
        ["pengirim_kota", "penerima_kota"],
        ["pengirim_kecamatan", "penerima_kecamatan"],
        ["pengirim_kelurahan", "penerima_kelurahan"],
        ["pengirim_kodepos", "penerima_kodepos"],
      ];

      map.forEach(([from, to]) => {
        const a = el(from);
        const b = el(to);
        if (a && b) b.value = a.value;
      });

      calculateDebounced();
    });
  }

  function bindSubmitGuard() {
    form.addEventListener("submit", () => {
      if (!btnSubmit) return;
      btnSubmit.disabled = true;
      btnSubmit.classList.add("opacity-70", "cursor-not-allowed");
      btnSubmit.textContent = "Menyimpan...";
    });
  }

  bindCalc();
  bindAsuransiPacking();
  bindCopyPengirim();
  bindSubmitGuard();
  calculate(); // initial paint
})();
