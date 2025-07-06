"ui";

const CONFIG_PATH = "/sdcard/server_url.txt";

// ✅ Tampilkan UI Setup
ui.layout(
  <vertical padding="16">
  
    <text text="🌐 Setup Server Automator" textSize="20sp" />
    <input id="serverInput" hint="https://example.com" />
    <button id="saveBtn" />
    
  </vertical>
);


// ⛏️ Set tombol setelah layout untuk hindari entity ref error
ui.saveBtn.setText("💾 Simpan dan Jalankan");

// 🔁 Load URL jika sudah tersimpan
if (files.exists(CONFIG_PATH)) {
  let saved = files.read(CONFIG_PATH);
  ui.serverInput.setText(saved.trim());
}

// 🚀 Ketika tombol disimpan
ui.saveBtn.click(() => {
  let url = ui.serverInput.text().trim();
  if (!url.startsWith("http")) {
    toast("❗ URL tidak valid");
    return;
  }

  files.write(CONFIG_PATH, url);
  toast("✅ URL disimpan");

  // ✅ Jalankan di thread, beri delay agar tidak error network
  threads.start(() => {
    toast("⏳ Menyiapkan koneksi...");
    sleep(1000);
    runListener(url);
  });
});

// 🧠 Fungsi utama
function runListener(baseUrl) {
  toast("🟢 Listener aktif");
  log("🌐 Server URL: " + baseUrl);

  const androidId = device.getAndroidId();
  const model = device.model;
  const brand = device.brand;
  const manufacturer = device.manufacturer;

  if (!androidId) {
    toast("❌ Android ID tidak ditemukan");
    log("❌ Gagal ambil Android ID");
    return;
  }

  const registerUrl = baseUrl + "/register-device";
  const taskUrl = baseUrl + "/device/" + androidId;

  // 🌐 Registrasi device
  try {
    log("📡 Registrasi ke: " + registerUrl);
    sleep(1000); // penting agar tidak crash

    let res = http.postJson(registerUrl, {
      android_id: androidId,
      model,
      brand,
      manufacturer
    });

    log("📥 Status: " + res.statusCode);

    if (res.statusCode !== 200 && res.statusCode !== 201) {
      log("❌ Body: " + res.body.string());
      toast("❌ Registrasi gagal: " + res.statusCode);
      return;
    }

    toast("✅ Terdaftar ke server");
  } catch (e) {
    log("❌ Exception saat registrasi: " + e);
    toast("❗ Gagal konek server");
    return;
  }

  // 🔁 Listener task
  while (true) {
    try {
      let res = http.get(taskUrl);
      let body = res.body.string().trim();

      if (res.statusCode === 200 && body) {
        log("📦 Task diterima");
        let task = JSON.parse(body);
        storages.create("remoteSteps").put("steps", task.steps);
        engines.execScriptFile("DynamicRunner.js");
      } else if (res.statusCode === 204) {
        log("⏳ Belum ada task");
      } else {
        log("❎ Respon error: " + res.statusCode);
      }
    } catch (e) {
      log("❌ Error polling: " + e);
    }

    sleep(3000);
  }
}
