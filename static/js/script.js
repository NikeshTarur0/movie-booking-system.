document.addEventListener('DOMContentLoaded', function () {
    // Seat selection logic for booking page
    const seats = document.querySelectorAll('.seat.available');
    const selectedSeatsInput = document.getElementById('selected_seats');
    const totalSeatsDisplay = document.getElementById('total_seats_display');
    const totalPriceDisplay = document.getElementById('total_price_display');
    const bookBtn = document.getElementById('book_btn');

    let selectedSeats = [];
    const pricePerSeatInput = document.getElementById('price_per_seat');
    let pricePerSeat = pricePerSeatInput ? parseFloat(pricePerSeatInput.value) : 0;

    seats.forEach(seat => {
        seat.addEventListener('click', function () {
            const seatNumber = this.getAttribute('data-seat');

            if (this.classList.contains('selected')) {
                // Deselect
                this.classList.remove('selected');
                selectedSeats = selectedSeats.filter(s => s !== seatNumber);
            } else {
                // Select
                this.classList.add('selected');
                selectedSeats.push(seatNumber);
            }

            updateBookingSummary();
        });
    });

    function updateBookingSummary() {
        if (selectedSeatsInput) {
            selectedSeatsInput.value = selectedSeats.join(',');
        }
        if (totalSeatsDisplay) {
            totalSeatsDisplay.textContent = selectedSeats.length;
        }
        if (totalPriceDisplay) {
            totalPriceDisplay.textContent = (selectedSeats.length * pricePerSeat).toFixed(2);
        }
        if (bookBtn) {
            bookBtn.disabled = selectedSeats.length === 0;
        }
    }
});
