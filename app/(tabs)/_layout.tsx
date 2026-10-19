import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { Colors } from '@/constants/Colors';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

type IoniconName = keyof typeof Ionicons.glyphMap;

function TabIcon({ name, focused }: { name: IoniconName; focused: boolean }) {
  return <Ionicons name={focused ? name : (`${name}-outline` as IoniconName)} size={24} color={focused ? Colors.accent : 'rgba(255,255,255,0.75)'} />;
}

export default function TabsLayout() {
  const insets = useSafeAreaInsets();
  const tabBarHeight = 56 + insets.bottom;

  return (
    <Tabs
      screenOptions={{
        headerStyle: { backgroundColor: Colors.primary },
        headerTintColor: Colors.white,
        headerTitleStyle: { fontWeight: '700', fontSize: 17 },
        tabBarStyle: {
          backgroundColor: Colors.primary,
          borderTopColor: 'rgba(255,255,255,0.15)',
          height: tabBarHeight,
          paddingBottom: insets.bottom + 4,
          paddingTop: 6,
        },
        tabBarActiveTintColor: Colors.accent,
        tabBarInactiveTintColor: 'rgba(255,255,255,0.75)',
        tabBarLabelStyle: { fontSize: 11, fontWeight: '600', marginTop: 2 },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'Home',
          tabBarIcon: ({ focused }) => <TabIcon name="grid" focused={focused} />,
          headerTitle: 'Build Mate',
        }}
      />
      <Tabs.Screen
        name="projects/index"
        options={{
          title: 'Projects',
          tabBarIcon: ({ focused }) => <TabIcon name="business" focused={focused} />,
          headerTitle: 'Projects',
        }}
      />
      <Tabs.Screen
        name="works/index"
        options={{
          title: 'Works',
          href: null,
          tabBarIcon: ({ focused }) => <TabIcon name="list" focused={focused} />,
          headerTitle: 'Works',
        }}
      />
      <Tabs.Screen
        name="meetings/index"
        options={{
          title: 'Meetings',
          tabBarIcon: ({ focused }) => <TabIcon name="calendar" focused={focused} />,
          headerTitle: 'Meetings',
        }}
      />
      <Tabs.Screen
        name="clients/index"
        options={{
          title: 'Customers',
          tabBarIcon: ({ focused }) => <TabIcon name="person-circle" focused={focused} />,
          headerTitle: 'Customers',
        }}
      />
      <Tabs.Screen
        name="profile"
        options={{
          title: 'Profile',
          tabBarIcon: ({ focused }) => <TabIcon name="person" focused={focused} />,
          headerShown: false,
        }}
      />
      <Tabs.Screen
        name="dealers/index"
        options={{
          title: 'Contacts',
          href: null,
          tabBarIcon: ({ focused }) => <TabIcon name="people" focused={focused} />,
          headerTitle: 'Contacts',
        }}
      />
      <Tabs.Screen
        name="admin/index"
        options={{
          title: 'Admin',
          href: null,
          tabBarIcon: ({ focused }) => <TabIcon name="shield-checkmark" focused={focused} />,
          headerShown: false,
        }}
      />
      {/* hidden screens */}
      <Tabs.Screen name="change-password" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="projects/[id]" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="projects/form" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="clients/[id]" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="clients/form" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="works/form" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="dealers/[id]" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="dealers/form" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="admin/contractors/index" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="admin/contractors/[id]" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="admin/contractors/form" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="admin/users/index" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="admin/users/[id]" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="admin/users/form" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="admin/roles/index" options={{ href: null, headerShown: false }} />
      <Tabs.Screen name="admin/roles/[id]" options={{ href: null, headerShown: false }} />
    </Tabs>
  );
}
