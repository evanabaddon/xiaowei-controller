toast("🟢 AutoX Dynamic Listener aktif");

let androidId = device.getAndroidId();
let model = device.model;
let brand = device.brand;
let manufacturer = device.manufacturer;

if (!androidId) {
  toast("❌ Gagal mendapatkan Android ID");
  log("❌ Tidak bisa mendapatkan Android ID. Keluar.");
  exit();
}

const baseUrl = "https://128a-103-189-201-91.ngrok-free.app";
const registerUrl = baseUrl + "/register-device";
const taskUrl = baseUrl + "/device/" + androidId;

// 🌐 1. Registrasi device
function registerDevice() {
  log("📲 Mendaftarkan device ke server...");

  let response = http.postJson(registerUrl, {
    android_id: androidId,
    model: model,
    brand: brand,
    manufacturer: manufacturer
  });

  if (response.statusCode === 200 || response.statusCode === 201) {
    toast("✅ Device terdaftar");
    log("✅ Device berhasil terdaftar");
  } else {
    log("⚠️ Gagal mendaftarkan device: " + response.statusCode);
    toast("❌ Registrasi gagal");
    exit();
  }
}

// 🌐 2. Cek apakah device sudah ada
function deviceExists() {
  let res = http.get(baseUrl + "/check-device/" + androidId);
  return res.statusCode === 200 && res.body.string().trim() === "ok";
}

// 🔁 3. Listener task
function startPolling() {
  while (true) {
    try {
      let res = http.get(taskUrl);
      let raw = res.body.string().trim();

      if (res.statusCode === 200 && raw !== "") {
        log("📦 Menerima perintah...");

        let task = JSON.parse(raw);

        // Simpan ke storage sementara
        storages.create("remoteSteps").put("steps", task.steps);

        // Jalankan runner
        engines.execScriptFile("DynamicRunner.js");
      } else if (res.statusCode === 204) {
        log("⏳ Tidak ada task tersedia");
      } else {
        log("❎ Respon kosong atau tidak valid");
      }
    } catch (e) {
      log("❌ Error polling: " + e);
    }

    sleep(3000);
  }
}

// Eksekusi
if (!deviceExists()) {
  registerDevice();
}

startPolling();
