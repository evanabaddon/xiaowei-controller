// DynamicRunner.js
let storage = storages.create("remoteSteps");
let steps = storage.get("steps", []);

function writeLog(message) {
  let timestamp = new Date().toISOString();
  files.append("/sdcard/dynamic_runner.txt", `[${timestamp}] ${message}\n`);
}

if (!auto.service) {
  toast("Layanan aksesibilitas belum aktif!");
  writeLog("Layanan aksesibilitas belum aktif!");
  auto.waitFor();
}

if (!requestScreenCapture()) {
    toast("Gagal meminta izin screenshot!");
    writeLog("❌ Tidak dapat meminta screenshot permission");
    exit();
}

function mediaScan(filePath) {
    app.sendBroadcast({
        action: android.intent.action.MEDIA_SCANNER_SCAN_FILE,
        data: "file://" + filePath
    });
}

function downloadImage(url, savePath) {
    try {
        // Pastikan folder Download ada
        let dir = files.getDir(savePath);
        if (!files.exists(dir)) {
            files.createWithDirs(dir);
        }

        let r = http.get(url);
        if (r.statusCode === 200) {
            files.writeBytes(savePath, r.body.bytes());
            writeLog("Gambar berhasil diunduh ke: " + savePath);

            // Media scan agar gambar muncul di galeri/Download
            mediaScan(savePath);
            writeLog("Media scan selesai untuk: " + savePath);

            return true;
        } else {
            writeLog("Gagal download image, status: " + r.statusCode);
        }
    } catch (e) {
        writeLog("Error download image: " + e);
    }
    return false;
}

function getTodayFilename() {
    let now = new Date();
    let yyyy = now.getFullYear();
    let mm = (now.getMonth() + 1).toString().padStart(2, "0");
    let dd = now.getDate().toString().padStart(2, "0");
    let hh = now.getHours().toString().padStart(2, "0");
    let min = now.getMinutes().toString().padStart(2, "0");
    let ss = now.getSeconds().toString().padStart(2, "0");
    return `${yyyy}${mm}${dd}_${hh}${min}${ss}`;
}
  
function getBaseUrl() {
const path = "/sdcard/server_url.txt";
return files.exists(path) ? files.read(path).trim() : null;
}
  
// 🧠 Handler untuk screenshot_only mode
function handleScreenshotOnly() {
    writeLog("📸 Menjalankan screenshot_only...");

    let filename = "Screenshot_" + getTodayFilename() + ".png";
    let file = "/sdcard/DCIM/Screenshots/" + filename;

    try {
        images.captureScreen(file);
        writeLog("💾 Screenshot disimpan langsung di: " + file);
    } catch (e) {
        writeLog("❌ Gagal menyimpan screenshot: " + e);
        return;
    }

    let serverUrl = getBaseUrl();
    if (!serverUrl) {
        writeLog("❌ Server URL tidak ditemukan");
        return;
    }

    let r = http.postMultipart(serverUrl + "/api/screenshot", {
        android_id: device.getAndroidId(),
        image: open(file)
    });

    writeLog("📤 Upload status: " + r.statusCode + ", " + r.body.string());

    toast("✅ Screenshot selesai");
    sleep(1000); 
    exit(); // keluar setelah 1 aksi
}


  // ✅ Cek jika steps adalah ["screenshot_only"]
if (steps.length === 1 && typeof steps[0] === "string" && steps[0] === "screenshot_only") {
    handleScreenshotOnly();
  }

for (let step of steps) {
  try {
      writeLog(`Menjalankan aksi: ${JSON.stringify(step)}`);
      switch (step.action) {
          case "toast":
              toast(step.text);
              writeLog(`Toast: ${step.text}`);
              break;
          case "sleep":
              let durationMs = (step.seconds || step.ms || 1) * 1000;
              sleep(durationMs);
              writeLog(`Sleep: ${durationMs} ms`);
              break;
          case "tap":
              click(step.x, step.y);
              writeLog(`Tap di (${step.x}, ${step.y})`);
              break;
          case "clickText":
              let el = textContains(step.text).findOne(3000);
              if (!el) el = descContains(step.text).findOne(3000);
              if (el) {
                  toast(`${step.text} ditemukan`);
                  el.click();
                  writeLog(`ClickText: ${step.text} (berhasil)`);
              } else {
                  click(360, 290);
                  toast(`${step.text} tidak ditemukan, klik koordinat`);
                  writeLog(`ClickText: ${step.text} (gagal ditemukan, klik koordinat 360,290)`);
              }
              break;
          case "swipe":
              swipe(step.x1, step.y1, step.x2, step.y2, step.duration || 300);
              writeLog(`Swipe dari (${step.x1},${step.y1}) ke (${step.x2},${step.y2}) durasi ${step.duration || 300} ms`);
              break;
          case "input":
              setText(step.text);
              writeLog(`Input teks: ${step.text}`);
              break;
           case "inputCaption":
            if (step.text) {
                setText(step.text);
                writeLog(`Input caption: ${step.text}`);
            } else {
                writeLog("Input caption: [KOSONG]");
            }
            break;
            case "uploadImage":
                if (step.image_url) {
                    let img = images.load(step.image_url);
                    if (img) {
                        let filename = "/sdcard/Download/upload_" + new Date().getTime() + ".jpg";
                        images.save(img, filename);
                        media.scanFile(filename);
                        toast("Gambar berhasil disimpan!");
                    } else {
                        toast("Gagal memuat gambar dari URL.");
                    }
                } else {
                    writeLog("Upload image: [URL KOSONG]");
                }
                break;

          case "launchApp":
              app.launchApp(step.app);
              writeLog(`Launch app: ${step.app}`);
              break;
          case "scrollUp":
              // Default: 1x scroll jika i tidak diisi
              let scrollUpCount = (typeof step.i === "number" && step.i > 0) ? step.i : 1;
              for (let n = 0; n < scrollUpCount; n++) {
                  swipe(500, 1200, 500, 400, 500);
                  writeLog(`Swipe up manual ke-${n+1}: (500,1500) -> (500,500) durasi 500 ms`);
                  sleep(1000); // jeda antar swipe, bisa diatur
              }
              break;
          case "scrollDown":
              let scrollDownCount = (typeof step.i === "number" && step.i > 0) ? step.i : 1;
              for (let n = 0; n < scrollDownCount; n++) {
                  swipe(500, 500, 500, 1500, 500);
                  writeLog(`Swipe down manual ke-${n+1}: (500,500) -> (500,1500) durasi 500 ms`);
                  sleep(1000);
              }
              break;
          case "back()":
              back();
              writeLog("Menekan tombol Back");
              break;
          case "home()":
              home();
              writeLog("Menekan tombol Home");
              break;
          case "powerDialog()":
              powerDialog();
              writeLog("Menampilkan Power Dialog");
              break;
          case "notifications()":
              notifications();
              writeLog("Menampilkan Notifications");
              break;
          case "quickSettings()":
              quickSettings();
              writeLog("Menampilkan Quick Settings");
              break;
          case "recents()":
              recents();
              writeLog("Menampilkan Recent Apps");
              break;
          case "dismissNotificationShade()":
              dismissNotificationShade();
              writeLog("Menutup Notification Shade");
              break;
          case "accessibilityShortcut()":
              accessibilityShortcut();
              writeLog("Menekan Accessibility Shortcut");
              break;
          case "accessibilityButtonChooser()":
              accessibilityButtonChooser();
              writeLog("Menampilkan Accessibility Button Chooser");
              break;
          case "accessibilityButton()":
              accessibilityButton();
              writeLog("Menekan Accessibility Button");
              break;
          case "accessibilityAllApps()":
              accessibilityAllApps();
              writeLog("Menampilkan All Apps (Aksesibilitas)");
              break;
           
            case "takeScreenshot":
                handleScreenshotOnly();
                break;
          default:
              toast("❓ Tidak dikenal: " + step.action);
              writeLog(`Aksi tidak dikenal: ${step.action}`);
      }
  } catch (e) {
      log("❌ Step error: " + step.action + " => " + e);
      writeLog(`❌ Error pada aksi ${step.action}: ${e}`);
  }
  sleep(500);
}
