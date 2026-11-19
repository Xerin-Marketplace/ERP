import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

/* Toast helper — window.toast('message', 'success'|'error') */
window.toast = (message, type = 'success') => {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
};

/* Top progress bar on navigation & form submits */
const progressBar = () => document.getElementById('top-progress');
window.addEventListener('beforeunload', () => progressBar()?.classList.add('active'));
document.addEventListener('submit', () => progressBar()?.classList.add('active'));

Alpine.start();
