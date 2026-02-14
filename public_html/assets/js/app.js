document.addEventListener('DOMContentLoaded', () => {

  const toggle = document.getElementById('menu-toggle');
  const menu   = document.getElementById('nav-links');

  if (!toggle || !menu) return;

  let isOpen = false; // ÉTAT UNIQUE

  toggle.addEventListener('click', (e) => {
    e.preventDefault();

    isOpen = !isOpen;

    if (isOpen) {
      menu.classList.add('show');
      toggle.textContent = '✕';
    } else {
      menu.classList.remove('show');
      toggle.textContent = '☰';
    }
  });

  // ⏰ Horloge (inchangée, sûre)
  function updateClock(){
    const now = new Date();
    const time = now.toLocaleTimeString('fr-FR');
    const date = now.toLocaleDateString('fr-FR', {
      weekday:'long',
      year:'numeric',
      month:'long',
      day:'numeric'
    });

    const t = document.getElementById('clock-time');
    const d = document.getElementById('clock-date');
    if (t) t.textContent = time;
    if (d) d.textContent = date.charAt(0).toUpperCase() + date.slice(1);
  }

  setInterval(updateClock, 1000);
  updateClock();
});
