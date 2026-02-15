// Set default values for show date and time safely (no runtime errors if elements missing)
document.addEventListener('DOMContentLoaded', function () {
    const dateEl = document.getElementById('show_date');
    const timeEl = document.getElementById('show_time');
    const now = new Date();

    if (dateEl) {
        try {
            dateEl.valueAsDate = now;
        } catch (e) {
            dateEl.value = now.toISOString().slice(0, 10);
        }
        dateEl.min = now.toISOString().slice(0, 10);
    }

    if (timeEl) {
        const nextHour = new Date(now);
        nextHour.setMinutes(0, 0, 0);
        nextHour.setHours(nextHour.getHours() + 1);
        const hh = nextHour.getHours().toString().padStart(2, '0');
        const mm = nextHour.getMinutes().toString().padStart(2, '0');
        timeEl.value = `${hh}:${mm}`;
    }
});