"ui";

const CONFIG_PATH = "/sdcard/server_url.txt";

// ✅ Layout UI sederhana dan aman
ui.layout(
  <vertical padding="16" bg="#FAFAFA">
    <text text="🌐 Setup Server" textSize="20sp" textColor="#212121" marginBottom="12"/>
    <input id="serverInput" hint="https://example.com" textColor="#000000" />
    <button id="saveBtn" text="💾 Simpan dan Jalankan" marginTop="16"/>
    <text text="© 2025 UtasAutoListener" gravity="center" textSize="12sp" textColor="#999999" marginTop="32"/>
  </vertical>
);

// 🔁 Load URL dari file jika ada
if (files.exists(CONFIG_PATH)) {
  let saved = files.read(CONFIG_PATH);
  ui.serverInput.setText(saved.trim());
}

// 🟢 Klik tombol
ui.saveBtn.click(() => {
  let url = ui.serverInput.text().trim();
  if (!url.startsWith("http")) {
    toast("❗ URL tidak valid");
    return;
  }

  files.write(CONFIG_PATH, url);
  toast("✅ URL disimpan");

  threads.start(() => {
    runListener(url);
  });
});

// 🔧 Fungsi utama listener
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

  // 🌐 Registrasi
  try {
    let res = http.postJson(registerUrl, {
      android_id: androidId,
      model,
      brand,
      manufacturer
    });

    log("📡 Registrasi ke: " + registerUrl);
    log("📥 Status: " + res.statusCode);

    if (res.statusCode !== 200 && res.statusCode !== 201) {
      toast("❌ Registrasi gagal");
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
