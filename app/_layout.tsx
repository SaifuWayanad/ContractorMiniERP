import React, { useEffect, useState, useCallback } from 'react';
import { Stack, router } from 'expo-router';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import * as SplashScreen from 'expo-splash-screen';
import * as Updates from 'expo-updates';
import { StatusBar } from 'expo-status-bar';
import { AuthContext, AuthUser } from '@/hooks/useAuth';
import { useRegisterPushToken } from '@/hooks/useRegisterPushToken';
import { login, logout, getStoredUser, getStoredToken, saveStoredUser } from '@/services/auth';
import { Colors } from '@/constants/Colors';

SplashScreen.preventAutoHideAsync();

async function checkForUpdate() {
  try {
    const result = await Updates.checkForUpdateAsync();
    if (result.isAvailable) {
      await Updates.fetchUpdateAsync();
      await Updates.reloadAsync();
    }
  } catch {
    // non-critical — silently skip if offline or in dev mode
  }
}

export default function RootLayout() {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    checkForUpdate();
  }, []);

  useEffect(() => {
    (async () => {
      try {
        const [storedUser, storedToken] = await Promise.all([getStoredUser(), getStoredToken()]);
        if (storedUser && storedToken) {
          setUser(storedUser);
          setToken(storedToken);
        }
      } catch {
        // ignore
      } finally {
        setIsLoading(false);
        await SplashScreen.hideAsync();
      }
    })();
  }, []);

  useEffect(() => {
    if (!isLoading) {
      if (user) {
        router.replace('/(tabs)');
      } else {
        router.replace('/(auth)/login');
      }
    }
  }, [isLoading, user]);

  const signIn = useCallback(async (username: string, password: string) => {
    const result = await login(username, password);
    setUser(result.user);
    setToken(result.token);
  }, []);

  const signOut = useCallback(async () => {
    await logout();
    setUser(null);
    setToken(null);
  }, []);

  const updateUser = useCallback(async (updated: AuthUser) => {
    await saveStoredUser(updated);
    setUser(updated);
  }, []);

  useRegisterPushToken(!!user);

  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <SafeAreaProvider>
        <AuthContext.Provider value={{ user, token, signIn, signOut, updateUser, isLoading }}>
          <StatusBar style="light" />
          <Stack screenOptions={{ headerShown: false }}>
            <Stack.Screen name="(auth)" />
            <Stack.Screen name="(tabs)" />
          </Stack>
        </AuthContext.Provider>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}
