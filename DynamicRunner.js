// DynamicRunner.js
let storage = storages.create("remoteSteps");
let steps = storage.get("steps", []);

for (let step of steps) {
  try {
    switch (step.action) {
      case "toast":
        toast(step.text);
        break;
      case "sleep":
        // Convert detik ke milidetik
        let durationMs = (step.seconds || 1) * 1000;
        sleep(durationMs);
        break;
      case "tap":
        click(step.x, step.y);
        break;
      case "clickText":
        let t = text(step.text).findOne(3000);
        if (t) t.click();
        break;
      case "swipe":
        swipe(step.x1, step.y1, step.x2, step.y2, step.duration || 300);
        break;
      case "input":
        setText(step.text);
        break;
      case "launchApp":
        app.launchApp(step.app);
        break;
      default:
        toast("❓ Tidak dikenal: " + step.action);
    }
  } catch (e) {
    log("❌ Step error: " + step.action + " => " + e);
  }
  sleep(500); // jeda antar step tetap 0.5 detik
}
