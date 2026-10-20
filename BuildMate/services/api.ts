import axios from 'axios';
import * as SecureStore from 'expo-secure-store';

// Set EXPO_PUBLIC_API_URL to point the app at a local server (e.g. http://10.0.2.2:8000 from the Android emulator).
export const API_BASE_URL = process.env.EXPO_PUBLIC_API_URL || 'https://buildmate.tech';

const api = axios.create({
  baseURL: `${API_BASE_URL}/api`,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: 15000,
});

// Attach token to every request
api.interceptors.request.use(async (config) => {
  const token = await SecureStore.getItemAsync('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle 401 → clear token
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      await SecureStore.deleteItemAsync('auth_token');
    }
    return Promise.reject(error);
  }
);

export default api;
