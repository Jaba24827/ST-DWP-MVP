import React, { useEffect, useState } from 'react';
import { useApi } from '../hooks/useApi.jsx';
import { useCan } from '../hooks/useSession.jsx';

export default function Documents() {
  const api = useApi();
  const canUpload = useCan('documents.upload');
  const [rows, setRows] = useState([]);
  const [error, setError] = useState(null);
  const [q, setQ] = useState('');

  const load = () => api(`/api/documents?q=${encodeURIComponent(q)}`)
    .then((d) => setRows(d.data || d))
    .catch((e) => setError(e.message));

  useEffect(() => { load(); /* eslint-disable-next-line */ }, []);

  return (
    <>
      <div className="page__head">
        <h1>Documents</h1>
        <p>Filtered by classification server-side before this list is built.</p>
      </div>
      {error && <div className="flash flash--error">{error}</div>}
      <form onSubmit={(e) => { e.preventDefault(); load(); }} style={{ marginBottom: 14 }}>
        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search documents" />
      </form>
      <div className="table-wrap">
        <table>
          <thead><tr><th>Document</th><th>Classification</th><th>Owner</th><th>Actions</th></tr></thead>
          <tbody>
            {rows.map((d) => (
              <tr key={d.id}>
                <td><b>{d.title}</b></td>
                <td><span className={`tag ${d.classification === 'restricted' ? 'tag--deny' : d.classification === 'internal' ? 'tag--warn' : 'tag--ok'}`}>{d.classification}</span></td>
                <td>{d.owner?.name}</td>
                <td><a className="btn btn--ghost btn--sm" href={`/documents/${d.id}/download`}>Open</a></td>
              </tr>
            ))}
            {!rows.length && <tr><td colSpan={4} className="empty"><b>Nothing to show</b></td></tr>}
          </tbody>
        </table>
      </div>
      {canUpload && <p style={{ fontSize: 12.5, color: 'var(--text-3)', marginTop: 10 }}>Uploads happen through the Blade screen or a dedicated form component — omitted here for brevity.</p>}
    </>
  );
}
