const { useState, useEffect } = React;

/* ---------------- Live Search Widget ---------------- */
function SearchWidget() {
    const [checkIn, setCheckIn] = useState('');
    const [checkOut, setCheckOut] = useState('');
    const [guests, setGuests] = useState(2);
    const [results, setResults] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const today = new Date().toISOString().split('T')[0];

    async function handleSearch(e) {
        e.preventDefault();
        setError('');
        if (checkIn && checkOut && checkOut <= checkIn) {
            setError('Check-out must be after check-in.');
            return;
        }
        setLoading(true);
        const qs = new URLSearchParams({ check_in: checkIn, check_out: checkOut, guests }).toString();
        const res = await apiGet('api/rooms_search.php?' + qs);
        setLoading(false);
        if (res.success) {
            setResults(res.data.rooms);
        } else {
            setError(res.message || 'Something went wrong.');
        }
    }

    return (
        <div>
            <form className="search-card" onSubmit={handleSearch}>
                <div className="field">
                    <label>Check In</label>
                    <input type="date" min={today} value={checkIn} onChange={e => setCheckIn(e.target.value)} required />
                </div>
                <div className="field">
                    <label>Check Out</label>
                    <input type="date" min={checkIn || today} value={checkOut} onChange={e => setCheckOut(e.target.value)} required />
                </div>
                <div className="field">
                    <label>Guests</label>
                    <select value={guests} onChange={e => setGuests(e.target.value)}>
                        {[1,2,3,4,5,6].map(n => <option key={n} value={n}>{n} Guest{n > 1 ? 's' : ''}</option>)}
                    </select>
                </div>
                <button type="submit" className="btn btn-gold" disabled={loading}>
                    {loading ? 'Searching…' : 'Check Availability'}
                </button>
            </form>
            {error && <p style={{color:'var(--danger)', marginTop:'12px', fontSize:'14px'}}>{error}</p>}

            {results && (
                <div style={{marginTop:'28px'}}>
                    {results.length === 0 ? (
                        <div className="empty-state card">
                            <div className="crest"><span>LS</span></div>
                            <p>No rooms available for those dates. Try adjusting your search.</p>
                        </div>
                    ) : (
                        <div className="rooms-grid">
                            {results.map(r => <RoomCard key={r.id} room={r} checkIn={checkIn} checkOut={checkOut} guests={guests} />)}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

/* ---------------- Room Card ---------------- */
function RoomCard({ room, checkIn, checkOut, guests }) {
    const img = room.cover_image || 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?q=80&w=800&auto=format&fit=crop';
    const bookUrl = 'rooms.php?room_id=' + room.id +
        (checkIn ? '&check_in=' + checkIn : '') +
        (checkOut ? '&check_out=' + checkOut : '') +
        (guests ? '&guests=' + guests : '');
    return (
        <div className="room-card">
            <div className="thumb" style={{backgroundImage: `url(${img})`}}>
                <div className="badge-price">{formatCurrency(room.price_per_night)}/night</div>
            </div>
            <div className="body">
                <h3>{room.room_type}</h3>
                <div className="meta">
                    <span>Room {room.room_number}</span>
                    <span>Up to {room.capacity} guests</span>
                </div>
                <p className="desc">{room.description}</p>
                <a href={bookUrl} className="btn btn-navy btn-block">Reserve This Room</a>
            </div>
        </div>
    );
}

/* ---------------- Featured Rooms (static list, no dates) ---------------- */
function RoomsGrid() {
    const [rooms, setRooms] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        apiGet('api/rooms_list.php').then(res => {
            if (res.success) setRooms(res.data.rooms.slice(0, 6));
            setLoading(false);
        });
    }, []);

    if (loading) return <p className="text-center loading-dot">Loading rooms…</p>;
    if (!rooms.length) return <p className="text-center">Rooms will appear here shortly.</p>;

    return (
        <div className="rooms-grid">
            {rooms.map(r => <RoomCard key={r.id} room={r} checkIn="" checkOut="" guests="" />)}
        </div>
    );
}

/* ---------------- Gallery ---------------- */
function Gallery() {
    const [images, setImages] = useState([]);
    useEffect(() => {
        apiGet('api/gallery_list.php').then(res => {
            if (res.success) setImages(res.data.images.filter(i => i.image_path));
        });
    }, []);

    const fallback = [
        'https://images.unsplash.com/photo-1611892440504-42a792e24d32?q=80&w=600&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1590490360182-c33d57733427?q=80&w=600&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?q=80&w=600&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?q=80&w=600&auto=format&fit=crop',
    ];
    const shown = images.length ? images.map(i => i.image_path) : fallback;

    return (
        <div className="gallery-grid">
            {shown.slice(0, 8).map((src, i) => <img key={i} src={src} alt="Luxury Stay property" loading="lazy" />)}
        </div>
    );
}

/* ---------------- Testimonials ---------------- */
function Testimonials() {
    const [items, setItems] = useState([]);
    useEffect(() => {
        apiGet('api/gallery_list.php').then(res => {
            if (res.success) setItems(res.data.testimonials);
        });
    }, []);

    if (!items.length) return <p className="text-center" style={{color:'rgba(255,255,255,0.6)'}}>Loading testimonials…</p>;

    return (
        <div className="testimonial-grid">
            {items.map(t => (
                <div className="testimonial-card" key={t.id}>
                    <p>"{t.testimonial_text}"</p>
                    <strong>— {t.testimonial_author}</strong>
                </div>
            ))}
        </div>
    );
}

/* ---------------- Contact Form ---------------- */
function ContactForm() {
    const [form, setForm] = useState({ name: '', email: '', message: '' });
    const [status, setStatus] = useState(null);
    const [sending, setSending] = useState(false);

    async function submit(e) {
        e.preventDefault();
        setSending(true);
        setStatus(null);
        const res = await apiPostJson('api/contact_submit.php', form);
        setSending(false);
        setStatus(res);
        if (res.success) setForm({ name: '', email: '', message: '' });
    }

    return (
        <form className="card" onSubmit={submit}>
            {status && <div className={`form-msg ${status.success ? 'success' : 'error'}`}>{status.message}</div>}
            <div className="form-row">
                <div className="form-group">
                    <label>Name</label>
                    <input value={form.name} onChange={e => setForm({...form, name: e.target.value})} required />
                </div>
                <div className="form-group">
                    <label>Email</label>
                    <input type="email" value={form.email} onChange={e => setForm({...form, email: e.target.value})} required />
                </div>
            </div>
            <div className="form-group">
                <label>Message</label>
                <textarea rows="4" value={form.message} onChange={e => setForm({...form, message: e.target.value})} required></textarea>
            </div>
            <button className="btn btn-gold btn-block" disabled={sending}>{sending ? 'Sending…' : 'Send Message'}</button>
        </form>
    );
}

/* ---------------- Mount ---------------- */
ReactDOM.createRoot(document.getElementById('search-root')).render(<SearchWidget />);
ReactDOM.createRoot(document.getElementById('rooms-root')).render(<RoomsGrid />);
ReactDOM.createRoot(document.getElementById('gallery-root')).render(<Gallery />);
ReactDOM.createRoot(document.getElementById('testimonials-root')).render(<Testimonials />);
ReactDOM.createRoot(document.getElementById('contact-root')).render(<ContactForm />);
