const tablesVersion = document.getElementById('pos-data').dataset.tablesVersion;
let currentTablesVersion = tablesVersion;
// ISO formatı ile gelen timestamp'leri JS Date objesine dönüştürmek için
function parseDateTime(dt) {
  return new Date(dt.replace(' ', 'T'));
}
function updateTimers() {
  document.querySelectorAll('.table-timer').forEach(el => {
    const openedAt = parseDateTime(el.dataset.openedAt);
    const now = new Date();
    let diff = Math.floor((now - openedAt) / 1000);
    const hours = Math.floor(diff / 3600);
    diff %= 3600;
    const minutes = Math.floor(diff / 60);
    const seconds = diff % 60;
    if (hours > 0) {
      el.textContent = `${hours} saat ${minutes} dk ${seconds} sn`;
    } else {
      el.textContent = `${minutes} dk ${seconds} sn`;
    }
  });
}
document.addEventListener('DOMContentLoaded', () => {
  updateTimers();
  setInterval(updateTimers, 1000);
  initTableWatcher();
});

function initTableWatcher() {
  setInterval(checkTableUpdates, 5000);
}

async function checkTableUpdates() {
  try {
    const resp = await fetch('api_tables_status.php', { cache: 'no-store' });
    const data = await resp.json();
    if (data.version !== currentTablesVersion) {
      reloadTables(data.version);
    }
  } catch (e) {
    console.error(e);
  }
}
async function reloadTables(newVersion) {
  try {
    const resp = await fetch('pos.php?partial=1', { cache: 'no-store' });
    const html = await resp.text();
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const wrapper = doc.querySelector('#tablesWrapper');
    if (wrapper) {
      document.getElementById('tablesWrapper').innerHTML = wrapper.innerHTML;
      updateTimers();
      currentTablesVersion = newVersion;
    }
  } catch (e) {
    console.error(e);
  }
}

