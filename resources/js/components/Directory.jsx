import React, { useEffect, useState } from 'react';
import { useApi } from '../hooks/useApi.jsx';

export default function Directory() {
  const api = useApi();
  const [rows, setRows] = useState([]);
  useEffect(() => { api('/api/directory').then((d) => setRows(d.data || d)).catch(() => {}); }, [api]);
  return (
    <>
      <div className="page__head"><h1>Staff directory</h1></div>
      <div className="table-wrap">
        <table>
          <thead><tr><th>Name</th><th>Sector</th><th>Site</th></tr></thead>
          <tbody>{rows.map((p) => <tr key={p.id}><td>{p.name}</td><td>{p.sector?.name}</td><td>{p.site?.name}</td></tr>)}</tbody>
        </table>
      </div>
    </>
  );
}
