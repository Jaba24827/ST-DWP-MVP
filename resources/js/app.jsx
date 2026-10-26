import React, { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { SessionProvider, useSession } from './hooks/useSession.jsx';
import AppShell from './components/AppShell.jsx';
import Dashboard from './components/Dashboard.jsx';
import Documents from './components/Documents.jsx';
import Directory from './components/Directory.jsx';
import AuditLog from './components/AuditLog.jsx';

function Protected({ perm, children }) {
  const { session, loading } = useSession();

  useEffect(() => {
    if (!loading && !session) {
      window.location.replace('/login');
    }
  }, [loading, session]);

  if (loading || !session) return null;

  if (perm && !session.permissions?.includes(perm)) {
    return <Navigate to="/dashboard" replace />;
  }

  return children;
}

function Root() {
  return (
    <BrowserRouter basename="/app">
      <SessionProvider>
        <Routes>
          <Route element={<AppShell />}>
            <Route
              path="/dashboard"
              element={
                <Protected perm="dashboard.view">
                  <Dashboard />
                </Protected>
              }
            />
            <Route
              path="/documents"
              element={
                <Protected perm="documents.view">
                  <Documents />
                </Protected>
              }
            />
            <Route
              path="/directory"
              element={
                <Protected perm="directory.view">
                  <Directory />
                </Protected>
              }
            />
            <Route
              path="/audit"
              element={
                <Protected perm="audit.view">
                  <AuditLog />
                </Protected>
              }
            />
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
          </Route>
        </Routes>
      </SessionProvider>
    </BrowserRouter>
  );
}

const el = document.getElementById('root');
if (el) createRoot(el).render(<Root />);
