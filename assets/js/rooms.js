const { useState, useEffect } = React;

function SearchBar({ onSearch, initial }) {
    const [checkIn, setCheckIn] = useState(initial.check_in || '');
    const [checkOut, setCheckOut] = useState(initial.check_out || '');
    const [guests, setGuests] = useState(initial.guests || 2);
    const today = new Date().toISOString().split('T')[0];

    function submit(e) {
        e.preventDefault();
        onSearch({ checkIn, checkOut, guests });
    }

    return (
        <form className="search-card" onSubmit={submit}>
            <div className="field">
                <label>Check In</label>
                <input type="date" min={today} value={checkIn} onChange={e => setCheckIn(e.target.value)} />
            </div>
            <div className="field">
                <label>Check Out</label>
                <input type="date" min={checkIn || today} value={checkOut} onChange={e => setCheckOut(e.target.value)} />
            </div>
            <div className="field">
                <label>Guests</label>
                <select value={guests} onChange={e => setGuests(e.target.value)}>
                    {[1,2,3,4,5,6].map(n => <option key={n} value={n}>{n} Guest{n > 1 ? 's' : ''}</option>)}
                </select>
            </div>
            <button type="submit" className="btn btn-gold">Search Rooms</button>
        </form>
    );
}

function RoomsList({ rooms, onBook }) {
    if (!rooms.length) {
        return (
            <div className="empty-state card">
                <div className="crest"><span>LS</span></div>
                <p>No rooms match your search. Try different dates or guest count.</p>
            </div>
        );
    }
    return (
        <div className="rooms-grid">
            {rooms.map(r => (
                <div className="room-card" key={r.id}>
                    <div className="thumb" style={{backgroundImage: `url(${r.cover_image || 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?q=80&w=800&auto=format&fit=crop'})`}}>
                        <div className="badge-price">{formatCurrency(r.price_per_night)}/night</div>
                    </div>
                    <div className="body">
                        <h3>{r.room_type}</h3>
                        <div className="meta"><span>Room {r.room_number}</span><span>Up to {r.capacity} guests</span></div>
                        <p className="desc">{r.description}</p>
                        <button className="btn btn-navy btn-block" onClick={() => onBook(r)}>Reserve This Room</button>
                    </div>
                </div>
            ))}
        </div>
    );
}

function BookingModal({ room, dates, onClose }) {
    const [checkIn, setCheckIn] = useState(dates.checkIn || '');
    const [checkOut, setCheckOut] = useState(dates.checkOut || '');
    const [guests, setGuests] = useState(dates.guests || 2);
    const [status, setStatus] = useState(null);
    const [submitting, setSubmitting] = useState(false);
    const today = new Date().toISOString().split('T')[0];

    const nights = (checkIn && checkOut && checkOut > checkIn)
        ? Math.round((new Date(checkOut) - new Date(checkIn)) / 86400000)
        : 0;
    const total = nights * room.price_per_night;

    async function confirmBooking() {
        if (!window.LS_LOGGED_IN) {
            window.location.href = 'login.php';
            return;
        }
        if (!checkIn || !checkOut || checkOut <= checkIn) {
            setStatus({ success: false, message: 'Please select valid check-in and check-out dates.' });
            return;
        }
        setSubmitting(true);
        const res = await apiPostJson('api/booking_create.php', {
            room_id: room.id, check_in: checkIn, check_out: checkOut, guests
        });
        setSubmitting(false);
        setStatus(res);
        if (res.success) {
            setTimeout(() => { window.location.href = 'dashboard.php?tab=bookings'; }, 1200);
        }
    }

    return (
        <div className="modal-overlay" onClick={(e) => e.target === e.currentTarget && onClose()}>
            <div className="modal-box">
                <div className="card-head">
                    <h3>Reserve {room.room_type}</h3>
                    <button className="icon-btn" onClick={onClose}>Close</button>
                </div>
                {status && <div className={`form-msg ${status.success ? 'success' : 'error'}`}>{status.message}</div>}
                <div className="form-row">
                    <div className="form-group">
                        <label>Check In</label>
                        <input type="date" min={today} value={checkIn} onChange={e => setCheckIn(e.target.value)} />
                    </div>
                    <div className="form-group">
                        <label>Check Out</label>
                        <input type="date" min={checkIn || today} value={checkOut} onChange={e => setCheckOut(e.target.value)} />
                    </div>
                </div>
                <div className="form-group">
                    <label>Guests</label>
                    <select value={guests} onChange={e => setGuests(e.target.value)}>
                        {Array.from({length: room.capacity}, (_, i) => i + 1).map(n => <option key={n} value={n}>{n} Guest{n > 1 ? 's' : ''}</option>)}
                    </select>
                </div>
                {nights > 0 && (
                    <div className="card" style={{background:'var(--ivory)', padding:'16px 18px', margin:'18px 0'}}>
                        <div className="flex-between"><span>{formatCurrency(room.price_per_night)} × {nights} night{nights > 1 ? 's' : ''}</span><strong>{formatCurrency(total)}</strong></div>
                    </div>
                )}
                <button className="btn btn-gold btn-block" onClick={confirmBooking} disabled={submitting}>
                    {submitting ? 'Confirming…' : (window.LS_LOGGED_IN ? 'Confirm Reservation' : 'Sign In to Book')}
                </button>
            </div>
        </div>
    );
}

function RoomsPage() {
    const [rooms, setRooms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [activeDates, setActiveDates] = useState({ checkIn: window.LS_PREFILL.check_in, checkOut: window.LS_PREFILL.check_out, guests: window.LS_PREFILL.guests });
    const [bookingRoom, setBookingRoom] = useState(null);

    async function search({ checkIn, checkOut, guests }) {
        setLoading(true);
        setActiveDates({ checkIn, checkOut, guests });
        const qs = new URLSearchParams({ check_in: checkIn || '', check_out: checkOut || '', guests: guests || 1 }).toString();
        const res = await apiGet('api/rooms_search.php?' + qs);
        setLoading(false);
        if (res.success) setRooms(res.data.rooms);
    }

    useEffect(() => {
        (async () => {
            const res = await apiGet('api/rooms_list.php');
            if (res.success) {
                setRooms(res.data.rooms);
                setLoading(false);
                if (window.LS_PREFILL.room_id) {
                    const target = res.data.rooms.find(r => String(r.id) === String(window.LS_PREFILL.room_id));
                    if (target) setBookingRoom(target);
                }
            }
        })();
    }, []);

    return (
        <div>
            <SearchBar onSearch={search} initial={window.LS_PREFILL} />
            <div style={{marginTop:'40px'}}>
                {loading ? <p className="text-center loading-dot">Loading rooms…</p> : <RoomsList rooms={rooms} onBook={setBookingRoom} />}
            </div>
            {bookingRoom && <BookingModal room={bookingRoom} dates={activeDates} onClose={() => setBookingRoom(null)} />}
        </div>
    );
}

ReactDOM.createRoot(document.getElementById('rooms-root')).render(<RoomsPage />);
