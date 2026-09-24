import React, { createContext, useContext, useEffect, useState, useCallback } from 'react';

/**
 * One fetch of /api/me populates the permission list the whole tree reads
 * from. Nothing in the presentation tier decides on its own who can do
 * what — it only reflects what the application tier already decided.
 */
const SessionContext = createContext(null);

export function SessionProvider({ children }) {
  const [session, setSession] = useState(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(() => {
    setLoading(true);
    fetch('/api/me', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then((r) => (r.ok ? r.json() : Promise.reject(r.status)))
      .then(setSession)
      .catch(() => setSession(null))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { refresh(); }, [refresh]);

  return (
    <SessionContext.Provider value={{ session, loading, refresh }}>
      {children}
    </SessionContext.Provider>
  );
}

export function useSession() {
  const ctx = useContext(SessionContext);
  if (!ctx) throw new Error('useSession must be used inside SessionProvider');
  return ctx;
}

/** True only if the server said so. A missing permission hides UI; it is
 *  never the reason a request is allowed — the API middleware decides that. */
export function useCan(permission) {
  const { session } = useSession();
  return !!session?.permissions?.includes(permission);
}
