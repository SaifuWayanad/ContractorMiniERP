import { useEffect } from 'react';
import { Platform } from 'react-native';
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
        if (!Device.isDevice) return;

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

        if (finalStatus !== 'granted') return;

        const tokenData = await Notifications.getExpoPushTokenAsync();
        await api.post('/mobile/device-token', { token: tokenData.data });
      } catch {
        // non-critical — silently ignore
      }
    })();
  }, [isLoggedIn]);
}
