import React, { useState } from 'react';
import { NavLink, Outlet } from 'react-router-dom';
import { useSession, useCan } from '../hooks/useSession.jsx';

/**
 * Same shell contract as the Blade layout: a white vertical sidebar, a
 * green wordmark, and navigation built only from permissions the server
 * confirmed. A role that lacks users.manage never receives a "Users"
 * link — there is nothing here for it to notice is missing.
 */
const NAV = [
  { group: 'Work', items: [
    { to: '/dashboard', perm: 'dashboard.view', label: 'Dashboard' },
    { to: '/documents', perm: 'documents.view', label: 'Documents' },
    { to: '/directory', perm: 'directory.view', label: 'Directory' },
  ]},
  { group: 'Administration', items: [
    { to: '/audit', perm: 'audit.view', label: 'Audit log' },
  ]},
];

export default function AppShell() {
  const { session } = useSession();
  const [collapsed, setCollapsed] = useState(false);
  const [drawer, setDrawer] = useState(false);

  return (
    <div className={`app ${collapsed ? 'is-collapsed' : ''} ${drawer ? 'is-drawer' : ''}`}>
      <div className="scrim" onClick={() => setDrawer(false)} />
      <aside className="sidebar">
        <div className="sidebar__head">
          <img src="/assets/img/logo.svg" alt="" width="36" height="36" style={{ marginRight: 11 }} />
          <div>
            <div style={{ fontWeight: 700, color: 'var(--brand)' }}>SL-DWP</div>
            <div style={{ fontSize: 11.5, color: 'var(--text-2)' }}>Digital Workplace</div>
          </div>
        </div>
        <nav className="sidebar__nav" aria-label="Main">
          {NAV.map((g) => {
            const visible = g.items.filter((i) => useCan(i.perm));
            if (!visible.length) return null;
            return (
              <React.Fragment key={g.group}>
                <div className="sidebar__group">{g.group}</div>
                {visible.map((i) => (
                  <NavLink key={i.to} to={i.to} className={({ isActive }) => `nav-item ${isActive ? 'is-active' : ''}`}>
                    {i.label}
                  </NavLink>
                ))}
              </React.Fragment>
            );
          })}
        </nav>
        <div className="sidebar__foot">SL-DWP · React + Laravel API</div>
      </aside>

      <div className="main">
        <header className="topbar">
          <button className="btn btn--ghost btn--sm" onClick={() => setDrawer((d) => !d)} aria-label="Toggle navigation">☰</button>
          <div style={{ flex: 1, fontSize: 13, color: 'var(--text-2)' }}>SL-DWP</div>
          {session && (
            <div className="topbar__who" style={{ display: 'flex', gap: 9, alignItems: 'center' }}>
              <span>{session.name}</span>
              <span style={{ color: 'var(--text-2)', fontSize: 11.5 }}>{session.role}</span>
            </div>
          )}
        </header>
        <div className="content"><div className="page"><Outlet /></div></div>
      </div>
    </div>
  );
}
