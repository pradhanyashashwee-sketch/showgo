// Admin Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Modal functionality
    const modal = document.getElementById('movieModal');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    const addMovieBtn = document.getElementById('addMovieBtn');
    const movieForm = document.getElementById('movieForm');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    
    // Open modal for adding new movie
    if (addMovieBtn) {
        addMovieBtn.addEventListener('click', function() {
            modal.style.display = 'flex';
            modalTitle.textContent = 'Add New Movie';
            submitBtn.name = 'add_movie';
            submitBtn.textContent = 'Add Movie';
            movieForm.reset();
            document.getElementById('movieId').value = '';
        });
    }
    
    // Close modal
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // AJAX form submission for add/update movie and showtimes (realtime updates)
    // Intercept movie form submission
    if (movieForm) {
        movieForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const fd = new FormData(movieForm);
            const action = submitBtn.name; // 'add_movie' or 'update_movie'
            fd.append(action, '1');
            
            try {
                const res = await fetch('admin_dashboard.php', {method: 'POST', body: fd});
                const text = await res.text();
                if (text.includes('Error')) {
                    alert('Error: ' + text.substring(text.indexOf('Error'), Math.min(text.indexOf('Error')+100, text.length)));
                } else {
                    alert(action === 'add_movie' ? 'Movie added successfully!' : 'Movie updated successfully!');
                    modal.style.display = 'none';
                    // Trigger realtime update immediately
                    if (window.triggerRealtimeUpdate) window.triggerRealtimeUpdate();
                }
            } catch (err) {
                console.error(err);
                alert('Error saving movie');
            }
        });
    }

    // Intercept showtime form submission
    const showtimeForm = document.querySelector('form[name="add_showtime_form"]') || 
                         Array.from(document.querySelectorAll('form')).find(f => f.textContent.includes('Show Time'));
    if (showtimeForm) {
        showtimeForm.addEventListener('submit', async function(e) {
            // Only intercept if this is the showtimes form
            if (!this.querySelector('[name="add_showtime"]')) return;
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('add_showtime', '1');
            
            try {
                const res = await fetch('admin_dashboard.php', {method: 'POST', body: fd});
                const text = await res.text();
                if (text.includes('Error')) {
                    alert('Error: ' + text.substring(text.indexOf('Error'), Math.min(text.indexOf('Error')+100, text.length)));
                } else {
                    alert('Showtime added successfully!');
                    this.reset();
                    // Trigger realtime update immediately
                    if (window.triggerRealtimeUpdate) window.triggerRealtimeUpdate();
                }
            } catch (err) {
                console.error(err);
                alert('Error saving showtime');
            }
        });
    }

    // Delete confirmation for movies
    document.querySelectorAll('button[name="delete_movie"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this movie? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Update movie status - with realtime trigger
    function attachMovieStatusHandlers() {
        document.querySelectorAll('.movie-status-select').forEach(select => {
            if (select.dataset.listenerAttached) return; // Skip if already attached
            select.addEventListener('change', async function() {
                const movieId = this.getAttribute('data-movie-id');
                const newStatus = this.value;
                
                try {
                    const fd = new FormData();
                    fd.append('ajax_action', 'update_movie_status');
                    fd.append('id', movieId);
                    fd.append('status', newStatus);
                    
                    const res = await fetch('admin_dashboard.php', {method: 'POST', body: fd});
                    const j = await res.json();
                    
                    if (j.success) {
                        console.log('Movie status updated successfully');
                        // Trigger realtime update to sync with public pages
                        setTimeout(() => {
                            if (window.triggerRealtimeUpdate) window.triggerRealtimeUpdate();
                        }, 300);
                    } else {
                        alert('Error updating movie status');
                        this.value = this.getAttribute('data-old-value');
                    }
                } catch (err) {
                    console.error(err);
                    alert('Error updating movie status');
                }
            });
            select.dataset.listenerAttached = 'true';
        });
    }
    
    // Attach handlers on load
    attachMovieStatusHandlers();
    
    // Re-attach if new elements are added (e.g., from realtime polling)
    setInterval(attachMovieStatusHandlers, 2000);
    
    // Update booking status - with retry logic
    function attachStatusSelectHandlers() {
        document.querySelectorAll('.status-select').forEach(select => {
            if (select.dataset.listenerAttached) return; // Skip if already attached
            select.addEventListener('change', function() {
                const bookingId = this.getAttribute('data-booking-id');
                const newStatus = this.value;
                const row = this.closest('tr');
                
                fetch('update-booking-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `booking_id=${bookingId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Immediately update the status badge in the table row
                        if (row) {
                            const statusBadge = row.querySelector('.status-badge');
                            if (statusBadge) {
                                statusBadge.className = 'status-badge status-' + newStatus;
                                statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                            }
                        }
                        alert('Booking status updated successfully!');
                        // Delay poller trigger to allow DB to commit
                        setTimeout(() => {
                            if (window.triggerRealtimeUpdate) window.triggerRealtimeUpdate();
                        }, 500);
                    } else {
                        alert('Error updating status: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating booking status');
                });
            });
            select.dataset.listenerAttached = 'true';
        });
    }
    
    // Attach handlers on load
    attachStatusSelectHandlers();
    
    // Re-attach if new elements are added (e.g., from realtime polling)
    setInterval(attachStatusSelectHandlers, 2000);
    
    // Set current date for showtime form
    const showDateInput = document.getElementById('show_date');
    if (showDateInput) {
        const today = new Date().toISOString().split('T')[0];
        showDateInput.value = today;
        showDateInput.min = today;
    }
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.display = 'none';
        });
    }, 5000);
});

// Search functionality
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let showRow = false;
        
        for (let j = 0; j < cells.length; j++) {
            if (cells[j]) {
                const text = cells[j].textContent || cells[j].innerText;
                if (text.toLowerCase().indexOf(filter) > -1) {
                    showRow = true;
                    break;
                }
            }
        }
        
        rows[i].style.display = showRow ? '' : 'none';
    }
}