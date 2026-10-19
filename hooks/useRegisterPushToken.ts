import { useEffect } from 'react';
import { Alert, Platform } from 'react-native';
import Constants from 'expo-constants';
import api from '@/services/api';

export function useRegisterPushToken(isLoggedIn: boolean) {
  useEffect(() => {
    if (!isLoggedIn) return;

    // expo-notifications push tokens are not available in Expo Go (SDK 53+).
    // They work only in a real build (APK / dev build). Skip silently here.
    if (Constants.appOwnership === 'expo') return;

    (async () => {
      try {
        const Device = (await import('expo-device')).default ?? await import('expo-device');
        if (!Device.isDevice) { Alert.alert('Push', 'Not a physical device'); return; }

        const Notifications = await import('expo-notifications');

        Notifications.setNotificationHandler({
          handleNotification: async () => ({
            shouldShowAlert: true,
            shouldPlaySound: true,
            shouldSetBadge: false,
            shouldShowBanner: true,
            shouldShowList: true,
          }),
        });

        if (Platform.OS === 'android') {
          await Notifications.setNotificationChannelAsync('meetings', {
            name: 'Meeting Reminders',
            importance: Notifications.AndroidImportance.HIGH,
            sound: 'default',
          });
        }

        const { status: existing } = await Notifications.getPermissionsAsync();
        let finalStatus = existing;

        if (existing !== 'granted') {
          const { status } = await Notifications.requestPermissionsAsync();
          finalStatus = status;
        }

        if (finalStatus !== 'granted') { Alert.alert('Push', 'Notification permission denied'); return; }

        // Native FCM token: sent straight to Firebase by the server, no Expo account needed.
        const tokenData = await Notifications.getDevicePushTokenAsync();
        await api.post('/mobile/device-token', { token: String(tokenData.data) });
      } catch (e: any) {
        Alert.alert('Push registration failed', String(e?.response?.data?.message ?? e?.message ?? e));
      }
    })();
  }, [isLoggedIn]);
}
