import * as SecureStore from 'expo-secure-store';
import api from './api';

export const TOKEN_KEY = 'auth_token';
export const USER_KEY = 'auth_user';

export async function login(username: string, password: string) {
  const res = await api.post('/mobile/login', { username, password });
  const { token, user } = res.data;
  await SecureStore.setItemAsync(TOKEN_KEY, token);
  await SecureStore.setItemAsync(USER_KEY, JSON.stringify(user));
  return { token, user };
}

export async function logout() {
  try {
    await api.post('/mobile/logout');
  } catch {
    // ignore network errors on logout
  }
  await SecureStore.deleteItemAsync(TOKEN_KEY);
  await SecureStore.deleteItemAsync(USER_KEY);
}

export async function getStoredUser() {
  const raw = await SecureStore.getItemAsync(USER_KEY);
  return raw ? JSON.parse(raw) : null;
}

export async function saveStoredUser(user: object) {
  await SecureStore.setItemAsync(USER_KEY, JSON.stringify(user));
}

export async function getStoredToken() {
  return SecureStore.getItemAsync(TOKEN_KEY);
}
