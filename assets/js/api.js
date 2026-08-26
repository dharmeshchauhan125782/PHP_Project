// Small fetch helper shared by all pages
async function apiGet(url) {
    const res = await fetch(url, { credentials: 'same-origin' });
    return res.json();
}
async function apiPostJson(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });
    return res.json();
}
async function apiPostForm(url, formData) {
    const res = await fetch(url, { method: 'POST', credentials: 'same-origin', body: formData });
    return res.json();
}
function formatCurrency(n) {
    return '₹' + Number(n).toLocaleString('en-IN', { maximumFractionDigits: 0 });
}
function formatDate(d) {
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}
