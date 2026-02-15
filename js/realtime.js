(function(){
  const POLL_INTERVAL = 5000;
  let pollTimeout;

  async function fetchJSON(url) {
    try {
      const res = await fetch(url, {cache: 'no-store'});
      return await res.json();
    } catch (e) {
      console.error('Fetch error', url, e);
      return null;
    }
  }
  function formatTime(t) {
    if (!t) return '';
    const parts = String(t).split(':');
    if (parts.length < 2) return t;
    let hour = parseInt(parts[0],10);
    const minute = parts[1];
    const ampm = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12 || 12;
    return hour + ':' + minute + ' ' + ampm;
  }
  function updateMoviesTable(data){
    const table = document.getElementById('moviesTable');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    tbody.innerHTML = '';
    if (!data || data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center">No movies found.</td></tr>';
      return;
    }
    data.forEach(movie => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${movie.id}</td>
        <td>${movie.poster_url ? '<img src="'+movie.poster_url+'" class="table-poster" />' : '<div class="no-poster">No Image</div>'}</td>
        <td><strong>${escapeHtml(movie.title)}</strong><br><small class="text-muted">${escapeHtml((movie.description||'').substring(0,100))}...</small></td>
        <td>${movie.duration_minutes} min</td>
        <td><span class="status-badge status-${movie.status}">${escapeHtml((movie.status||'').replace('_',' '))}</span></td>
        <td>${movie.release_date || ''}</td>
        <td>
          <a href="admin_dashboard.php?tab=movies&edit=${movie.id}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>
          <button data-id="${movie.id}" class="btn btn-sm btn-danger ajax-delete-movie"><i class="fas fa-trash"></i> Delete</button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }
  function updateShowtimesTable(data){
    const table = document.getElementById('showtimesTable');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    tbody.innerHTML = '';
    if (!data || data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center">No showtimes found.</td></tr>';
      return;
    }
    data.forEach(s => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${s.id}</td>
        <td>${escapeHtml(s.movie_title)}</td>
        <td>${s.show_date}</td>
        <td>${formatTime(s.show_time)}</td>
        <td>Rs.${parseFloat(s.price).toFixed(2)}</td>
        <td>${s.available_seats}</td>
        <td>
          <button class="btn btn-sm btn-warning edit-showtime" data-id="${s.id}"><i class="fas fa-edit"></i> Edit</button>
          <button class="btn btn-sm btn-danger ajax-delete-showtime" data-id="${s.id}"><i class="fas fa-trash"></i> Delete</button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }
  function escapeHtml(str){
    if (!str) return '';
    return String(str).replace(/[&<>"'`]/g, function (s) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;",'`':'&#96;'})[s];
    });
  }
  async function poll() {
    // Public API endpoints
    const moviesResp = await fetchJSON('api/movies.php');
    const showResp = await fetchJSON('api/showtimes.php');
    const bookingResp = await fetchJSON('api/bookings.php');
    // Update admin tables if present
    if (moviesResp && moviesResp.success) updateMoviesTable(moviesResp.data);
    if (showResp && showResp.success) updateShowtimesTable(showResp.data);
    // Update public pages if present
    if (moviesResp && moviesResp.success) updatePublicMoviesList(moviesResp.data);
    if (showResp && showResp.success) updateBookingList(showResp.data);
    // Update user dashboard booking statuses if present
    if (bookingResp && bookingResp.success) updateBookingStatuses(bookingResp.data);
    attachDeleteHandlers();
    pollTimeout = setTimeout(poll, POLL_INTERVAL);
  }
  function updatePublicMoviesList(data){
    const container = document.querySelector('.movies-container');
    if (!container) return;
    container.innerHTML = '';
    data.forEach(movie => {
      const card = document.createElement('div');
      card.className = 'movies-card';
      card.innerHTML = `
        <div class="posters">
          <a href="movie${movie.id}.php#${encodeURIComponent(movie.title)}">
            <img src="${escapeHtml(movie.poster_url || 'images/default.jpg')}" alt="${escapeHtml(movie.title)}">
          </a>
        </div>
        <h3>${escapeHtml(movie.title)}</h3>
        <p>Duration: ${movie.duration_minutes} min</p>
        <div class="buttons">
          <div class="timings">
            <!-- showtimes for this movie will be inserted by booking page poller if needed -->
          </div>
        </div>
      `;
      container.appendChild(card);
    });
  }
  function updateBookingList(showtimes){
    // If we're on booking page, update the details-section with showtime cards grouped by movie
    const details = document.querySelector('.details-section');
    if (!details) return;
    // Group showtimes by movie
    const byMovie = {};
    showtimes.forEach(s => {
      if (!byMovie[s.movie_id]) byMovie[s.movie_id] = {movie_title: s.movie_title, showtimes: []};
      byMovie[s.movie_id].showtimes.push(s);
    });
    details.innerHTML = '';
    Object.keys(byMovie).forEach(mid => {
      const m = byMovie[mid];
      const card = document.createElement('div');
      card.className = 'movie-card';
      card.innerHTML = `
        <div class="poster">
          <img src="images/default.jpg" alt="${escapeHtml(m.movie_title)}">
        </div>
        <div class="details">
          <h2>${escapeHtml(m.movie_title)}</h2>
          <h3>Available Timings</h3>
          <div class="timings">
            ${m.showtimes.map(s => `<a class="time-btn1" href="user-login.php?movie_id=${s.movie_id}&movie_title=${encodeURIComponent(s.movie_title)}&show_time=${encodeURIComponent(s.show_time)}">${formatTime(s.show_time)}</a>`).join('')}
          </div>
        </div>
      `;
      details.appendChild(card);
    });
  }
  function updateBookingStatuses(bookings){
    // Update booking status badges on user dashboard
    if (!bookings || bookings.length === 0) return;
    console.debug('updateBookingStatuses', bookings);
    
    bookings.forEach(b => {
      // Try to update user dashboard card first
      const card = document.querySelector('.booking-card[data-booking-id="' + b.booking_id + '"]');
      if (card) {
        const statusBadge = card.querySelector('.status-badge');
        if (statusBadge) {
          const oldStatus = statusBadge.textContent.trim().toLowerCase();
          const newStatus = b.status.toLowerCase();
          console.debug('updating booking card', b.booking_id, 'from', oldStatus, 'to', newStatus);
          if (oldStatus !== newStatus) {
            statusBadge.className = 'status-badge status-' + newStatus;
            statusBadge.textContent = b.status.charAt(0).toUpperCase() + b.status.slice(1);
            console.debug('card badge updated');
          }
        }
      }
      
      // Also try to update admin bookings table row
      const adminTable = document.querySelector('table.data-table');
      if (adminTable) {
        const rows = adminTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
          const firstCell = row.querySelector('td');
          if (firstCell && firstCell.textContent.includes(b.booking_id)) {
            // Found the row for this booking, check if dropdown differs from API status
            const dropdown = row.querySelector('.status-select');
            if (dropdown && dropdown.value !== b.status.toLowerCase()) {
              // User likely just changed it, don't overwrite
              console.debug('skipping update for booking', b.booking_id, 'dropdown has', dropdown.value, 'api has', b.status);
              return;
            }
            // Update status badge
            const statusBadge = row.querySelector('.status-badge');
            if (statusBadge) {
              const oldStatus = statusBadge.textContent.trim().toLowerCase();
              const newStatus = b.status.toLowerCase();
              if (oldStatus !== newStatus) {
                statusBadge.className = 'status-badge status-' + newStatus;
                statusBadge.textContent = b.status.charAt(0).toUpperCase() + b.status.slice(1);
                console.debug('admin table badge updated for booking', b.booking_id);
              }
            }
          }
        });
      }
    });
  }
  // Expose poll and immediate trigger globally for use by admin forms
  window.triggerRealtimeUpdate = function(){
    clearTimeout(pollTimeout);
    poll();
  };
  window.startRealtimePoller = function(){
    poll();
  };
  function attachDeleteHandlers(){
    document.querySelectorAll('.ajax-delete-movie').forEach(btn => {
      btn.onclick = async function(){
        if (!confirm('Delete this movie?')) return;
        const id = this.dataset.id;
        const fd = new FormData(); fd.append('ajax_action','delete_movie'); fd.append('id', id);
        const res = await fetch('admin_dashboard.php', {method:'POST', body: fd});
        try{
          const j = await res.json(); if (j.success) this.closest('tr').remove(); else alert('Delete failed');
        }catch(e){ console.error(e); }
        try{ if (window.triggerRealtimeUpdate) window.triggerRealtimeUpdate(); }catch(e){}
      }
    });
    document.querySelectorAll('.ajax-delete-showtime').forEach(btn => {
      btn.onclick = async function(){
        if (!confirm('Delete this showtime?')) return;
        const id = this.dataset.id;
        const fd = new FormData(); fd.append('ajax_action','delete_showtime'); fd.append('id', id);
        const res = await fetch('admin_dashboard.php', {method:'POST', body: fd});
        try{
          const j = await res.json(); if (j.success) this.closest('tr').remove(); else alert('Delete failed');
        }catch(e){ console.error(e); }
        try{ if (window.triggerRealtimeUpdate) window.triggerRealtimeUpdate(); }catch(e){}
      }
    });
  }

  // Start polling when DOM ready
  document.addEventListener('DOMContentLoaded', function(){
    window.startRealtimePoller();
  });
})();