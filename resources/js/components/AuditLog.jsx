import React, { useEffect, useState } from 'react';
import { useApi } from '../hooks/useApi.jsx';

export default function AuditLog() {
  const api = useApi();
  const [rows, setRows] = useState([]);
  useEffect(() => { api('/api/audit').then((d) => setRows(d.data || d)).catch(() => {}); }, [api]);
  return (
    <>
      <div className="page__head"><h1>Audit log</h1></div>
      <div className="table-wrap">
        <table>
          <thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Result</th></tr></thead>
          <tbody>{rows.map((l) => (
            <tr key={l.id}><td className="mono">{l.created_at}</td><td className="mono">{l.actor}</td><td className="mono">{l.action}</td>
              <td><span className={`tag ${l.result === 'denied' ? 'tag--deny' : 'tag--ok'}`}>{l.result}</span></td></tr>
          ))}</tbody>
        </table>
      </div>
    </>
  );
}
