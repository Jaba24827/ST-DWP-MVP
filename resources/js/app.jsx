import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { SessionProvider, useSession } from './hooks/useSession.jsx';
import AppShell from './components/AppShell.jsx';
import Dashboard from './components/Dashboard.jsx';
import Documents from './components/Documents.jsx';
import Directory from './components/Directory.jsx';
import AuditLog from './components/AuditLog.jsx';

/**
 * Presentation tier (React). Talks to the Laravel API in routes/api.php
 * over the session cookie — no token stored in JS, so there is nothing
 * for an XSS payload to steal and reuse.
 *
 * The Blade stack in resources/views is the reference implementation;
 * this tree is offered for a team that wants the React front end named
 * in the architecture document. Both call the same controllers, the same
 * middleware, and read the same permissions.
 */
function Protected({ perm, children }) {
  const { session, loading } = useSession();
  if (loading) return null;
  if (!session) return <Navigate to="/login" replace />;
  if (perm && !session.permissions.includes(perm)) return <Navigate to="/dashboard" replace />;
  return children;
}

function Root() {
  return (
    <BrowserRouter>
      <SessionProvider>
        <Routes>
          <Route element={<AppShell />}>
            <Route path="/dashboard" element={<Protected perm="dashboard.view"><Dashboard /></Protected>} />
            <Route path="/documents" element={<Protected perm="documents.view"><Documents /></Protected>} />
            <Route path="/directory" element={<Protected perm="directory.view"><Directory /></Protected>} />
            <Route path="/audit" element={<Protected perm="audit.view"><AuditLog /></Protected>} />
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
          </Route>
        </Routes>
      </SessionProvider>
    </BrowserRouter>
  );
}

const el = document.getElementById('root');
if (el) createRoot(el).render(<Root />);
