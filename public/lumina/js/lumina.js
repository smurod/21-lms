/* ============================================================
   LUMINA — Alpine.js helpers and component
   ============================================================ */

document.addEventListener('alpine:init', () => {
  Alpine.data('luminaApp', () => ({
    tab: 'dashboard',
    query: '',

    tasks: [
      { id: 1, time: '09:00', title: 'Morning meditation', desc: '15 minutes mindfulness session', done: false, color: '#22d3ee' },
      { id: 2, time: '10:30', title: 'Team sync meeting', desc: 'Discuss Q3 roadmap with stakeholders', done: true, color: '#a855f7' },
      { id: 3, time: '13:45', title: 'Deep work: Landing page redesign', desc: 'Finish hero section animations', done: false, color: '#22d3ee' },
      { id: 4, time: '16:00', title: 'Review PRs & feedback', desc: 'Frontend component updates', done: false, color: '#a855f7' },
    ],

    projects: [
      { id: 1, title: 'Mobile App Redesign', progress: 67, due: 'Aug 12', color: '#22d3ee' },
      { id: 2, title: 'AI Productivity Coach', progress: 34, due: 'Sep 3', color: '#a855f7' },
      { id: 3, title: 'Brand Identity System', progress: 91, due: 'Jul 28', color: '#eab308' },
    ],

    // ----- INIT -----
    init() {
      // Animate all counters on load
      this.$nextTick(() => {
        document.querySelectorAll('[data-counter]').forEach(el => {
          animateCounter(el, parseInt(el.dataset.counter, 10));
        });
      });

      // Read tab from URL hash
      if (location.hash) {
        const initial = location.hash.slice(1);
        if (['dashboard', 'calendar', 'progress', 'projects', 'activities'].includes(initial)) {
          this.tab = initial;
        }
      }
    },

    // ----- TAB SWITCHING -----
    activateTab(key) {
      this.tab = key;
      if (history.replaceState) {
        history.replaceState(null, '', '#' + key);
      }
    },

    // ----- TASK TOGGLE + CONFETTI -----
    allDone() {
      return this.tasks.length > 0 && this.tasks.every(t => t.done);
    },

    toggleTask(task) {
      task.done = !task.done;
      if (this.allDone()) showConfetti();
    },

    addTask() {
      const colors = ['#22d3ee', '#a855f7'];
      const hour = (9 + Math.floor(Math.random() * 9)).toString().padStart(2, '0');
      this.tasks.push({
        id: Date.now(),
        time: hour + ':00',
        title: 'New focus block',
        desc: 'Added from dashboard',
        done: false,
        color: colors[Math.floor(Math.random() * colors.length)]
      });
    },

    // ----- SEARCH -----
    filteredTasks() {
      const q = this.query.trim().toLowerCase();
      if (!q) return this.tasks;
      return this.tasks.filter(t =>
        t.title.toLowerCase().includes(q) ||
        t.desc.toLowerCase().includes(q)
      );
    }
  }));
});


// ============================================================
// HELPERS — plain JS functions (not Alpine data)
// ============================================================

// Animate a counter element from 0 to target value
function animateCounter(el, target) {
  if (isNaN(target)) return;
  const text = el.textContent;
  const suffixMatch = text.match(/^([\d,]+)(.*)$/);
  const suffix = suffixMatch ? suffixMatch[2] : '';
  const format = (n) => n.toLocaleString() + suffix;

  el.textContent = format(0);
  const duration = 1200;
  const start = performance.now();
  // ease-out cubic
  const ease = t => 1 - Math.pow(1 - t, 3);

  function tick(now) {
    const t = Math.min((now - start) / duration, 1);
    el.textContent = format(Math.round(target * ease(t)));
    if (t < 1) requestAnimationFrame(tick);
    else el.textContent = format(target);
  }
  requestAnimationFrame(tick);
}


// Show confetti — 80 particles falling from top
function showConfetti() {
  const colors = ['#22d3ee', '#a855f7', '#eab308', '#67e8f9'];
  const container = document.createElement('div');
  container.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:100;overflow:hidden;';
  document.body.appendChild(container);

  for (let i = 0; i < 80; i++) {
    const piece = document.createElement('div');
    piece.className = 'confetti-piece';
    piece.style.left = Math.random() * 100 + 'vw';
    piece.style.background = colors[Math.floor(Math.random() * colors.length)];
    piece.style.setProperty('--dx', (Math.random() - 0.5) * 300 + 'px');
    piece.style.setProperty('--rot', (Math.random() * 720 - 360) + 'deg');
    piece.style.animationDuration = (2 + Math.random() * 1.5) + 's';
    container.appendChild(piece);
  }

  setTimeout(() => container.remove(), 4500);
}


// Smooth scroll for calendar TODAY button
document.addEventListener('click', (e) => {
  if (e.target.closest('[data-action="cal-today"]')) {
    document.querySelector('.cal-container')?.scrollIntoView({
      behavior: 'smooth',
      block: 'start'
    });
  }
});
