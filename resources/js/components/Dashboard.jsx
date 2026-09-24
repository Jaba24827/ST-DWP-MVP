import React, { useEffect, useState } from 'react';
import { useApi } from '../hooks/useApi.jsx';

export default function Dashboard() {
  const api = useApi();
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    api('/api/dashboard').then(setData).catch((e) => setError(e.message));
  }, [api]);

  if (error) return <div className="flash flash--error">{error}</div>;
  if (!data) return null;

  return (
    <>
      <div className="page__head"><h1>Dashboard</h1></div>
      <div className="grid grid--kpi">
        <div className="kpi"><span>Active accounts</span><b>{data.activeUsers}</b></div>
        <div className="kpi"><span>Documents you can open</span><b>{data.visibleDocs}</b><em>of {data.heldDocs} held</em></div>
        <div className={`kpi ${data.denials7d > 0 ? 'kpi--alert' : ''}`}><span>Denied requests, 7 days</span><b>{data.denials7d}</b></div>
      </div>
    </>
  );
}
