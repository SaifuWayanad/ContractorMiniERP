import { router } from 'expo-router';

/**
 * Go back if there is a screen underneath, otherwise open the home tab.
 * A screen opened directly (e.g. after a reload) has no history, and router.back() does nothing there.
 */
export function goBack() {
  if (router.canGoBack()) {
    router.back();
  } else {
    router.replace('/(tabs)');
  }
}
