import { useEffect, useState } from 'react';

const STORAGE_KEY = 'srnPc';

function readTokenFromUrl(): string {
  const params = new URLSearchParams(window.location.search);
  return (params.get('token') ?? '').trim();
}

export function useStationToken() {
  const [token, setToken] = useState('');

  useEffect(() => {
    const fromUrl = readTokenFromUrl();
    const stored = (localStorage.getItem(STORAGE_KEY) ?? '').trim();

    if (fromUrl) {
      localStorage.setItem(STORAGE_KEY, fromUrl);
      setToken(fromUrl);
      return;
    }

    if (stored) {
      const url = new URL(window.location.href);
      url.searchParams.set('token', stored);
      window.history.replaceState({}, '', url.toString());
      setToken(stored);
      return;
    }

    const generated = generateToken();
    localStorage.setItem(STORAGE_KEY, generated);
    const url = new URL(window.location.href);
    url.searchParams.set('token', generated);
    window.history.replaceState({}, '', url.toString());
    setToken(generated);
  }, []);

  return token;
}

function generateToken(): string {
  const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let prefix = '';
  for (let i = 0; i < 6; i++) {
    prefix += letters[Math.floor(Math.random() * letters.length)];
  }
  return prefix + Date.now();
}
