// Search functionality
function search() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const bookings = document.querySelectorAll('#bookedContent .booking-item');
    
    bookings.forEach(booking => {
        const text = booking.textContent.toLowerCase();
        booking.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

// Toggle sidebar
document.addEventListener('DOMContentLoaded', function() {
    // Load initial booking data
    loadBookings();

    // Add event listeners for controls
    document.querySelectorAll('.controls button').forEach(button => {
        button.addEventListener('click', function() {
            if (this.classList.contains('sort-btn')) {
                sortBookings();
            } else if (this.classList.contains('filter-btn')) {
                showFilters();
            } else if (this.classList.contains('close-btn')) {
                closeSection();
            }
        });
    });

    // Add event listener for search input
    document.getElementById('searchInput').addEventListener('input', search);
});

function loadBookings() {
    fetch('get_bookings.php')
        .then(response => response.json())
        .then(data => {
            displayBookings(data);
        })
        .catch(error => console.error('Error loading bookings:', error));
}

function displayBookings(bookings) {
    const container = document.getElementById('bookedContent');
    container.innerHTML = '';
    
    bookings.forEach(booking => {
        const bookingElement = document.createElement('div');
        bookingElement.classList.add('booking-item');
        bookingElement.innerHTML = `
            <p><strong>ID:</strong> ${booking.id}</p>
            <p><strong>User:</strong> ${booking.username}</p>
            <p><strong>Court:</strong> ${booking.court_name}</p>
            <p><strong>Time:</strong> ${new Date(booking.booking_time).toLocaleString()}</p>
            <p><strong>Payment:</strong> ${booking.payment_method}</p>
            <p><strong>Status:</strong> ${booking.status}</p>
        `;
        container.appendChild(bookingElement);
    });
}

function sortBookings() {
    const container = document.getElementById('bookedContent');
    const bookings = Array.from(container.children);
    
    bookings.sort((a, b) => {
        const timeA = new Date(a.querySelector('p:nth-child(4)').textContent.split(': ')[1]);
        const timeB = new Date(b.querySelector('p:nth-child(4)').textContent.split(': ')[1]);
        return timeA - timeB;
    });
    
    bookings.forEach(booking => container.appendChild(booking));
}

function showFilters() {
    // Implement filter functionality here
    console.log('Showing filters...');
}

function closeSection() {
    document.querySelector('.booked-section').style.display = 'none';
}