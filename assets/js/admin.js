const { useState, useEffect } = React;

const TAB_TITLES = { overview: 'Overview', bookings: 'Bookings', rooms: 'Rooms', users: 'Users', gallery: 'Gallery' };
const ROOM_TYPES = ['Standard Room', 'Deluxe Room', 'Super Deluxe Room', 'Suite Room'];

/* ================= OVERVIEW ================= */
function OverviewTab() {
    const [stats, setStats] = useState(null);
    useEffect(() => { apiGet('api/dashboard_stats.php').then(res => res.success && setStats(res.data)); }, []);
    if (!stats) return <p className="loading-dot">Loading stats…</p>;

    return (
        <div>
            <div className="stat-grid">
                <div className="stat-card"><span className="label">Total Rooms</span><strong>{stats.total_rooms}</strong></div>
                <div className="stat-card"><span className="label">Available Rooms</span><strong>{stats.available_rooms}</strong></div>
                <div className="stat-card"><span className="label">Occupied Rooms</span><strong>{stats.occupied_rooms}</strong></div>
                <div className="stat-card gold"><span className="label">Approved Revenue</span><strong>{formatCurrency(stats.revenue)}</strong></div>
            </div>
            <div className="stat-grid" style={{gridTemplateColumns:'repeat(3,1fr)'}}>
                <div className="stat-card"><span className="label">Registered Guests</span><strong>{stats.total_users}</strong></div>
                <div className="stat-card"><span className="label">Total Bookings</span><strong>{stats.total_bookings}</strong></div>
                <div className="stat-card"><span className="label">Pending Approval</span><strong>{stats.pending_bookings}</strong></div>
            </div>
            <div className="card">
                <div className="card-head"><h3>Recent Bookings</h3></div>
                {!stats.recent_bookings.length ? <p style={{color:'var(--ink-soft)'}}>No bookings yet.</p> : (
                    <table>
                        <thead><tr><th>Guest</th><th>Room</th><th>Check In</th><th>Check Out</th><th>Total</th><th>Status</th></tr></thead>
                        <tbody>
                            {stats.recent_bookings.map(b => (
                                <tr key={b.id}>
                                    <td>{b.guest}</td>
                                    <td>{b.room_type} ({b.room_number})</td>
                                    <td>{formatDate(b.check_in)}</td>
                                    <td>{formatDate(b.check_out)}</td>
                                    <td>{formatCurrency(b.total_price)}</td>
                                    <td><span className={`pill pill-${b.status}`}>{b.status}</span></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </div>
    );
}

/* ================= BOOKINGS ================= */
function BookingsTab() {
    const [bookings, setBookings] = useState([]);
    const [filter, setFilter] = useState('');
    const [loading, setLoading] = useState(true);

    async function load() {
        setLoading(true);
        const qs = filter ? '?status=' + filter : '';
        const res = await apiGet('api/bookings_manage.php' + qs);
        if (res.success) setBookings(res.data.bookings);
        setLoading(false);
    }
    useEffect(() => { load(); }, [filter]);

    async function act(id, action) {
        if (action === 'delete' && !confirm('Delete this booking permanently?')) return;
        await apiPostJson('api/bookings_manage.php', { id, action });
        load();
    }

    return (
        <div className="card">
            <div className="card-head">
                <h3>All Bookings</h3>
                <select value={filter} onChange={e => setFilter(e.target.value)} style={{padding:'8px 12px', border:'1px solid var(--line)', borderRadius:'6px'}}>
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved (Occupied)</option>
                    <option value="checked_out">Checked Out</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            {loading ? <p className="loading-dot">Loading…</p> : !bookings.length ? (
                <div className="empty-state"><div className="crest"><span>LS</span></div><p>No bookings found.</p></div>
            ) : (
                <table>
                    <thead><tr><th>Guest</th><th>Room</th><th>Dates</th><th>Guests</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        {bookings.map(b => (
                            <tr key={b.id}>
                                <td>{b.guest_name}<br/><span style={{fontSize:'12px', color:'var(--ink-soft)'}}>{b.guest_email}</span></td>
                                <td>{b.room_type}<br/><span style={{fontSize:'12px', color:'var(--ink-soft)'}}>Room {b.room_number}</span></td>
                                <td>{formatDate(b.check_in)} → {formatDate(b.check_out)}</td>
                                <td>{b.guests}</td>
                                <td>{formatCurrency(b.total_price)}</td>
                                <td><span className={`pill pill-${b.status}`}>{b.status}</span></td>
                                <td>
                                    <div className="action-row">
                                        {b.status === 'pending' && <>
                                            <button className="icon-btn" onClick={() => act(b.id, 'approve')}>Approve</button>
                                            <button className="icon-btn" onClick={() => act(b.id, 'reject')}>Reject</button>
                                        </>}
                                        {b.status === 'approved' && (
                                            <button className="icon-btn" onClick={() => act(b.id, 'checkout')}>Checkout</button>
                                        )}
                                        <button className="icon-btn" onClick={() => act(b.id, 'delete')}>Delete</button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}

/* ================= ROOMS ================= */
function RoomFormModal({ room, onClose, onSaved }) {
    const isEdit = !!room;
    const [form, setForm] = useState({
        room_number: room?.room_number || '',
        room_type: room?.room_type || '',
        description: room?.description || '',
        price_per_night: room?.price_per_night || '',
        capacity: room?.capacity || 2,
        status: room?.status || 'available',
    });
    const [coverFile, setCoverFile] = useState(null);
    const [galleryFiles, setGalleryFiles] = useState([]);
    const [saving, setSaving] = useState(false);
    const [status, setStatus] = useState(null);

    async function submit(e) {
        e.preventDefault();
        setSaving(true);
        const fd = new FormData();
        fd.append('action', 'save');
        if (isEdit) fd.append('id', room.id);
        Object.entries(form).forEach(([k, v]) => fd.append(k, v));
        if (coverFile) fd.append('cover_image', coverFile);
        galleryFiles.forEach(f => fd.append('gallery_images[]', f));

        const res = await apiPostForm('api/rooms_manage.php', fd);
        setSaving(false);
        setStatus(res);
        if (res.success) { onSaved(); }
    }

    return (
        <div className="modal-overlay" onClick={(e) => e.target === e.currentTarget && onClose()}>
            <div className="modal-box">
                <div className="card-head"><h3>{isEdit ? 'Edit Room' : 'Add New Room'}</h3><button className="icon-btn" onClick={onClose}>Close</button></div>
                {status && <div className={`form-msg ${status.success ? 'success' : 'error'}`}>{status.message}</div>}
                <form onSubmit={submit}>
                    <div className="form-row">
                        <div className="form-group"><label>Room Number</label><input value={form.room_number} onChange={e => setForm({...form, room_number: e.target.value})} required /></div>
                        <div className="form-group">
                            <label>Room Type</label>
                            <select value={form.room_type} onChange={e => setForm({...form, room_type: e.target.value})} required>
                                <option value="" disabled>Select a type…</option>
                                {ROOM_TYPES.map(t => <option key={t} value={t}>{t}</option>)}
                            </select>
                        </div>
                    </div>
                    <div className="form-group"><label>Description</label><textarea rows="3" value={form.description} onChange={e => setForm({...form, description: e.target.value})}></textarea></div>
                    <div className="form-row">
                        <div className="form-group"><label>Price / Night (₹)</label><input type="number" min="0" step="0.01" value={form.price_per_night} onChange={e => setForm({...form, price_per_night: e.target.value})} required /></div>
                        <div className="form-group"><label>Capacity</label><input type="number" min="1" value={form.capacity} onChange={e => setForm({...form, capacity: e.target.value})} required /></div>
                    </div>
                    <div className="form-group">
                        <label>Status</label>
                        <select value={form.status} onChange={e => setForm({...form, status: e.target.value})}>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                        <small style={{color:'var(--ink-soft)'}}>Available/Occupied normally update automatically when a booking is approved or checked out — only override manually for walk-ins or edge cases.</small>
                    </div>
                    <div className="form-group"><label>Cover Image</label><input type="file" accept="image/*" onChange={e => setCoverFile(e.target.files[0])} /></div>
                    <div className="form-group"><label>Additional Gallery Images</label><input type="file" accept="image/*" multiple onChange={e => setGalleryFiles(Array.from(e.target.files))} /></div>
                    <button className="btn btn-gold btn-block" disabled={saving}>{saving ? 'Saving…' : (isEdit ? 'Update Room' : 'Create Room')}</button>
                </form>
            </div>
        </div>
    );
}

function RoomsTab() {
    const [rooms, setRooms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [editing, setEditing] = useState(null);
    const [showForm, setShowForm] = useState(false);

    async function load() {
        setLoading(true);
        const res = await apiGet('api/rooms_manage.php');
        if (res.success) setRooms(res.data.rooms);
        setLoading(false);
    }
    useEffect(() => { load(); }, []);

    async function deleteRoom(id) {
        if (!confirm('Delete this room? This cannot be undone.')) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        await apiPostForm('api/rooms_manage.php', fd);
        load();
    }

    async function deleteImage(imgId) {
        const fd = new FormData();
        fd.append('action', 'delete_image');
        fd.append('image_id', imgId);
        await apiPostForm('api/rooms_manage.php', fd);
        load();
    }

    return (
        <div className="card">
            <div className="card-head">
                <h3>Rooms ({rooms.length} / 10)</h3>
                <button className="btn btn-gold btn-sm" disabled={rooms.length >= 10} title={rooms.length >= 10 ? 'Room limit reached (10 max)' : ''} onClick={() => { setEditing(null); setShowForm(true); }}>+ Add Room</button>
            </div>
            {loading ? <p className="loading-dot">Loading…</p> : (
                <table>
                    <thead><tr><th>Room</th><th>Type</th><th>Price</th><th>Capacity</th><th>Status</th><th>Gallery</th><th>Actions</th></tr></thead>
                    <tbody>
                        {rooms.map(r => (
                            <tr key={r.id}>
                                <td>{r.room_number}</td>
                                <td>{r.room_type}</td>
                                <td>{formatCurrency(r.price_per_night)}</td>
                                <td>{r.capacity}</td>
                                <td><span className={`pill pill-${r.status}`}>{r.status}</span></td>
                                <td>
                                    <div style={{display:'flex', gap:'4px', flexWrap:'wrap', maxWidth:'140px'}}>
                                        {r.images.map(img => (
                                            <div key={img.id} style={{position:'relative'}}>
                                                <img src={'../' + img.image_path} style={{width:'32px', height:'32px', objectFit:'cover', borderRadius:'4px'}} />
                                                <button onClick={() => deleteImage(img.id)} title="Remove" style={{position:'absolute', top:'-6px', right:'-6px', background:'var(--danger)', color:'#fff', border:'none', borderRadius:'50%', width:'16px', height:'16px', fontSize:'10px', lineHeight:'16px', padding:0}}>×</button>
                                            </div>
                                        ))}
                                    </div>
                                </td>
                                <td>
                                    <div className="action-row">
                                        <button className="icon-btn" onClick={() => { setEditing(r); setShowForm(true); }}>Edit</button>
                                        <button className="icon-btn" onClick={() => deleteRoom(r.id)}>Delete</button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
            {showForm && <RoomFormModal room={editing} onClose={() => setShowForm(false)} onSaved={() => { setShowForm(false); load(); }} />}
        </div>
    );
}

/* ================= USERS ================= */
function UsersTab() {
    const [users, setUsers] = useState([]);
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(true);

    async function load() {
        setLoading(true);
        const qs = search ? '?search=' + encodeURIComponent(search) : '';
        const res = await apiGet('api/users_manage.php' + qs);
        if (res.success) setUsers(res.data.users);
        setLoading(false);
    }
    useEffect(() => { const t = setTimeout(load, 300); return () => clearTimeout(t); }, [search]);

    async function remove(id) {
        if (!confirm('Remove this user account?')) return;
        await apiPostJson('api/users_manage.php', { action: 'delete', id });
        load();
    }

    return (
        <div className="card">
            <div className="card-head">
                <h3>Users ({users.length})</h3>
                <input placeholder="Search by name or email…" value={search} onChange={e => setSearch(e.target.value)} style={{padding:'8px 12px', border:'1px solid var(--line)', borderRadius:'6px', width:'240px'}} />
            </div>
            {loading ? <p className="loading-dot">Loading…</p> : !users.length ? (
                <div className="empty-state"><div className="crest"><span>LS</span></div><p>No users found.</p></div>
            ) : (
                <table>
                    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th></th></tr></thead>
                    <tbody>
                        {users.map(u => (
                            <tr key={u.id}>
                                <td>{u.name}</td><td>{u.email}</td><td>{u.phone || '—'}</td><td>{formatDate(u.created_at)}</td>
                                <td><button className="icon-btn" onClick={() => remove(u.id)}>Remove</button></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}

/* ================= GALLERY ================= */
function GalleryTab() {
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [form, setForm] = useState({ title: '', category: 'general', testimonial_text: '', testimonial_author: '' });
    const [file, setFile] = useState(null);
    const [saving, setSaving] = useState(false);

    async function load() {
        setLoading(true);
        const res = await apiGet('api/gallery_manage.php');
        if (res.success) setItems(res.data.items);
        setLoading(false);
    }
    useEffect(() => { load(); }, []);

    async function submit(e) {
        e.preventDefault();
        setSaving(true);
        const fd = new FormData();
        fd.append('action', 'save');
        Object.entries(form).forEach(([k, v]) => fd.append(k, v));
        if (file) fd.append('image', file);
        await apiPostForm('api/gallery_manage.php', fd);
        setSaving(false);
        setForm({ title: '', category: 'general', testimonial_text: '', testimonial_author: '' });
        setFile(null);
        load();
    }

    async function remove(id) {
        if (!confirm('Delete this gallery item?')) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        await apiPostForm('api/gallery_manage.php', fd);
        load();
    }

    return (
        <div>
            <div className="card">
                <div className="card-head"><h3>Add Gallery Item / Testimonial</h3></div>
                <form onSubmit={submit}>
                    <div className="form-row">
                        <div className="form-group"><label>Title</label><input value={form.title} onChange={e => setForm({...form, title: e.target.value})} required /></div>
                        <div className="form-group">
                            <label>Category</label>
                            <select value={form.category} onChange={e => setForm({...form, category: e.target.value})}>
                                <option value="general">General Photo</option>
                                <option value="testimonial">Testimonial</option>
                            </select>
                        </div>
                    </div>
                    {form.category === 'testimonial' ? (
                        <div className="form-row">
                            <div className="form-group"><label>Testimonial Text</label><textarea rows="2" value={form.testimonial_text} onChange={e => setForm({...form, testimonial_text: e.target.value})}></textarea></div>
                            <div className="form-group"><label>Author</label><input value={form.testimonial_author} onChange={e => setForm({...form, testimonial_author: e.target.value})} /></div>
                        </div>
                    ) : (
                        <div className="form-group"><label>Image</label><input type="file" accept="image/*" onChange={e => setFile(e.target.files[0])} /></div>
                    )}
                    <button className="btn btn-gold" disabled={saving}>{saving ? 'Saving…' : 'Add Item'}</button>
                </form>
            </div>

            <div className="card">
                <div className="card-head"><h3>Gallery Items ({items.length})</h3></div>
                {loading ? <p className="loading-dot">Loading…</p> : (
                    <table>
                        <thead><tr><th>Preview</th><th>Title</th><th>Category</th><th></th></tr></thead>
                        <tbody>
                            {items.map(i => (
                                <tr key={i.id}>
                                    <td>{i.image_path ? <img src={'../' + i.image_path} style={{width:'48px', height:'48px', objectFit:'cover', borderRadius:'6px'}} /> : '—'}</td>
                                    <td>{i.title}{i.testimonial_text ? <div style={{fontSize:'12px', color:'var(--ink-soft)', maxWidth:'300px'}}>"{i.testimonial_text}" — {i.testimonial_author}</div> : null}</td>
                                    <td>{i.category}</td>
                                    <td><button className="icon-btn" onClick={() => remove(i.id)}>Delete</button></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </div>
    );
}

/* ================= APP SHELL ================= */
function AdminApp() {
    const [tab, setTab] = useState('overview');

    useEffect(() => {
        document.getElementById('page-title').textContent = TAB_TITLES[tab];
        document.querySelectorAll('.dash-link[data-tab]').forEach(el => {
            el.classList.toggle('active', el.dataset.tab === tab);
        });
    }, [tab]);

    useEffect(() => {
        document.querySelectorAll('.dash-link[data-tab]').forEach(el => {
            el.addEventListener('click', (e) => { e.preventDefault(); setTab(el.dataset.tab); });
        });
    }, []);

    if (tab === 'overview') return <OverviewTab />;
    if (tab === 'bookings') return <BookingsTab />;
    if (tab === 'rooms') return <RoomsTab />;
    if (tab === 'users') return <UsersTab />;
    if (tab === 'gallery') return <GalleryTab />;
    return null;
}

ReactDOM.createRoot(document.getElementById('admin-root')).render(<AdminApp />);
