import { useEffect } from 'react';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { Platform } from 'react-native';
import api from '@/services/api';

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge: false,
    shouldShowBanner: true,
    shouldShowList: true,
  }),
});

export function useRegisterPushToken(isLoggedIn: boolean) {
  useEffect(() => {
    if (!isLoggedIn) return;

    (async () => {
      if (!Device.isDevice) return; // skip simulators

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
      const token = tokenData.data;

      try {
        await api.post('/mobile/device-token', { token });
      } catch {
        // non-critical — silently ignore
      }
    })();
  }, [isLoggedIn]);
}
