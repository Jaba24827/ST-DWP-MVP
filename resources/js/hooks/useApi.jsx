import { useCallback } from 'react';

/**
 * Thin fetch wrapper: same-origin credentials (the session cookie carries
 * auth), the CSRF header Laravel expects on state-changing requests, and a
 * single place that turns a 403 into a message the UI can show instead of
 * a raw stack trace.
 */
function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export function useApi() {
  return useCallback(async (path, options = {}) => {
    const res = await fetch(path, {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': options.body instanceof FormData ? undefined : 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        ...(options.headers || {}),
      },
      ...options,
    });

    if (res.status === 403) {
      throw new Error('Access denied. Your role does not hold the permission this needs.');
    }
    if (!res.ok) {
      const body = await res.json().catch(() => ({}));
      throw new Error(body.message || `Request failed (${res.status})`);
    }
    return res.status === 204 ? null : res.json();
  }, []);
}
