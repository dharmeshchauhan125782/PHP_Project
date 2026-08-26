const { useState, useEffect } = React;

function ProfileTab() {
    const [user, setUser] = useState(null);
    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');
    const [status, setStatus] = useState(null);
    const [saving, setSaving] = useState(false);

    const [pwForm, setPwForm] = useState({ current_password: '', new_password: '' });
    const [pwStatus, setPwStatus] = useState(null);
    const [pwSaving, setPwSaving] = useState(false);

    useEffect(() => {
        apiGet('api/profile.php').then(res => {
            if (res.success) {
                setUser(res.data.user);
                setName(res.data.user.name);
                setPhone(res.data.user.phone || '');
            }
        });
    }, []);

    async function saveProfile(e) {
        e.preventDefault();
        setSaving(true);
        const res = await apiPostJson('api/profile.php', { action: 'update_profile', name, phone });
        setSaving(false);
        setStatus(res);
    }

    async function changePassword(e) {
        e.preventDefault();
        setPwSaving(true);
        const res = await apiPostJson('api/profile.php', { action: 'change_password', ...pwForm });
        setPwSaving(false);
        setPwStatus(res);
        if (res.success) setPwForm({ current_password: '', new_password: '' });
    }

    if (!user) return <p className="loading-dot">Loading profile…</p>;

    return (
        <div>
            <div className="card">
                <div className="card-head"><h3>Profile Details</h3></div>
                {status && <div className={`form-msg ${status.success ? 'success' : 'error'}`}>{status.message}</div>}
                <form onSubmit={saveProfile}>
                    <div className="form-row">
                        <div className="form-group">
                            <label>Full Name</label>
                            <input value={name} onChange={e => setName(e.target.value)} required />
                        </div>
                        <div className="form-group">
                            <label>Phone Number</label>
                            <input value={phone} onChange={e => setPhone(e.target.value)} />
                        </div>
                    </div>
                    <div className="form-group">
                        <label>Email Address</label>
                        <input value={user.email} disabled style={{background:'#f3f3ee', color:'var(--ink-soft)'}} />
                    </div>
                    <button className="btn btn-navy" disabled={saving}>{saving ? 'Saving…' : 'Save Changes'}</button>
                </form>
            </div>

            <div className="card">
                <div className="card-head"><h3>Change Password</h3></div>
                {pwStatus && <div className={`form-msg ${pwStatus.success ? 'success' : 'error'}`}>{pwStatus.message}</div>}
                <form onSubmit={changePassword}>
                    <div className="form-row">
                        <div className="form-group">
                            <label>Current Password</label>
                            <input type="password" value={pwForm.current_password} onChange={e => setPwForm({...pwForm, current_password: e.target.value})} required />
                        </div>
                        <div className="form-group">
                            <label>New Password</label>
                            <input type="password" minLength="6" value={pwForm.new_password} onChange={e => setPwForm({...pwForm, new_password: e.target.value})} required />
                        </div>
                    </div>
                    <button className="btn btn-ghost" disabled={pwSaving}>{pwSaving ? 'Updating…' : 'Update Password'}</button>
                </form>
            </div>
        </div>
    );
}

function BookingsTab() {
    const [bookings, setBookings] = useState([]);
    const [loading, setLoading] = useState(true);
    const [msg, setMsg] = useState(null);

    async function load() {
        setLoading(true);
        const res = await apiGet('api/booking_list.php');
        if (res.success) setBookings(res.data.bookings);
        setLoading(false);
    }
    useEffect(() => { load(); }, []);

    async function cancelBooking(id) {
        if (!confirm('Cancel this booking?')) return;
        const res = await apiPostJson('api/booking_cancel.php', { booking_id: id });
        setMsg(res);
        load();
    }

    if (loading) return <p className="loading-dot">Loading bookings…</p>;

    return (
        <div className="card">
            <div className="card-head"><h3>My Bookings</h3></div>
            {msg && <div className={`form-msg ${msg.success ? 'success' : 'error'}`}>{msg.message}</div>}
            {!bookings.length ? (
                <div className="empty-state">
                    <div className="crest"><span>LS</span></div>
                    <p>You haven't made any bookings yet.</p>
                    <a href="rooms.php" className="btn btn-gold" style={{marginTop:'14px', display:'inline-flex'}}>Browse Rooms</a>
                </div>
            ) : (
                <table>
                    <thead>
                        <tr><th>Room</th><th>Check In</th><th>Check Out</th><th>Guests</th><th>Total</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        {bookings.map(b => (
                            <tr key={b.id}>
                                <td>{b.room_type}<br/><span style={{fontSize:'12px', color:'var(--ink-soft)'}}>Room {b.room_number}</span></td>
                                <td>{formatDate(b.check_in)}</td>
                                <td>{formatDate(b.check_out)}</td>
                                <td>{b.guests}</td>
                                <td>{formatCurrency(b.total_price)}</td>
                                <td><span className={`pill pill-${b.status}`}>{b.status}</span></td>
                                <td>
                                    {(b.status === 'pending' || b.status === 'approved') && (
                                        <button className="icon-btn" onClick={() => cancelBooking(b.id)}>Cancel</button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}

function Dashboard() {
    const [tab, setTab] = useState(window.LS_INITIAL_TAB || 'overview');

    useEffect(() => {
        document.getElementById('nav-overview').classList.toggle('active', tab === 'overview');
        document.getElementById('nav-bookings').classList.toggle('active', tab === 'bookings');
    }, [tab]);

    useEffect(() => {
        document.getElementById('nav-overview').addEventListener('click', (e) => { e.preventDefault(); setTab('overview'); });
        document.getElementById('nav-bookings').addEventListener('click', (e) => { e.preventDefault(); setTab('bookings'); });
    }, []);

    return tab === 'overview' ? <ProfileTab /> : <BookingsTab />;
}

ReactDOM.createRoot(document.getElementById('dashboard-root')).render(<Dashboard />);
