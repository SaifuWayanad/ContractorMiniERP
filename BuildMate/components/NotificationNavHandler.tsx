import React, { useRef, useEffect } from 'react';
import * as Notifications from 'expo-notifications';
import { router } from 'expo-router';
import { AuthUser } from '@/hooks/useAuth';

export default function NotificationNavHandler({ user }: { user: AuthUser | null }) {
  const lastNotificationResponse = Notifications.useLastNotificationResponse();
  const handledNotificationId = useRef<string | null>(null);

  useEffect(() => {
    if (!lastNotificationResponse || !user) return;

    const { identifier, content } = lastNotificationResponse.notification.request;
    if (handledNotificationId.current === identifier) return;
    handledNotificationId.current = identifier;

    const data = content.data ?? {};
    if (data.screen === 'meetings') {
      router.push('/(tabs)/meetings');
    } else if (data.projectId) {
      router.push(`/(tabs)/projects/${data.projectId}`);
    }
  }, [lastNotificationResponse, user]);

  return null;
}
