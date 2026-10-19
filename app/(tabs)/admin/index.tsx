import React from 'react';
import { View, Text, ScrollView, StyleSheet, TouchableOpacity } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { ScreenHeader } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import { useAuth } from '@/hooks/useAuth';

type MenuCard = {
  icon: keyof typeof Ionicons.glyphMap;
  title: string;
  subtitle: string;
  route: string;
  accent?: boolean;
};

const MENU: MenuCard[] = [
  {
    icon: 'business',
    title: 'Contractors',
    subtitle: 'Manage contractor accounts',
    route: '/(tabs)/admin/contractors',
  },
  {
    icon: 'people',
    title: 'Users',
    subtitle: 'Manage user accounts',
    route: '/(tabs)/admin/users',
  },
  {
    icon: 'shield',
    title: 'Roles',
    subtitle: 'Roles & permissions',
    route: '/(tabs)/admin/roles',
  },
];

export default function AdminHubScreen() {
  const { user } = useAuth();
  const initials = (user?.name ?? '?')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((w: string) => w[0].toUpperCase())
    .join('');

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader title="Admin" subtitle="Management console" />
      <ScrollView contentContainerStyle={s.content} showsVerticalScrollIndicator={false}>
        <View style={s.profileCard}>
          <View style={s.avatar}>
            <Text style={s.avatarText}>{initials}</Text>
          </View>
          <View style={{ marginLeft: 14 }}>
            <Text style={s.profileName}>{user?.name ?? 'Admin'}</Text>
            <Text style={s.profileRole}>Administrator</Text>
          </View>
        </View>

        <Text style={s.sectionTitle}>Management</Text>
        {MENU.map((item) => (
          <TouchableOpacity
            key={item.route}
            style={s.card}
            onPress={() => router.push(item.route as any)}
            activeOpacity={0.75}
          >
            <View style={s.iconWrap}>
              <Ionicons name={item.icon} size={24} color={Colors.accent} />
            </View>
            <View style={{ flex: 1, marginLeft: 14 }}>
              <Text style={s.cardTitle}>{item.title}</Text>
              <Text style={s.cardSub}>{item.subtitle}</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={Colors.textMuted} />
          </TouchableOpacity>
        ))}
      </ScrollView>
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  content: { padding: 16, paddingBottom: 40 },
  profileCard: {
    backgroundColor: Colors.primary,
    borderRadius: 14,
    padding: 20,
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 24,
  },
  avatar: {
    width: 52, height: 52, borderRadius: 26,
    backgroundColor: Colors.accent,
    alignItems: 'center', justifyContent: 'center',
  },
  avatarText: { fontSize: 20, fontWeight: '800', color: Colors.primary },
  profileName: { fontSize: 17, fontWeight: '800', color: Colors.white },
  profileRole: { fontSize: 13, color: 'rgba(255,255,255,0.7)', marginTop: 2 },
  sectionTitle: { fontSize: 12, fontWeight: '700', color: Colors.textMuted, textTransform: 'uppercase', letterSpacing: 0.8, marginBottom: 10 },
  card: {
    backgroundColor: Colors.white,
    borderRadius: 12,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.08,
    shadowRadius: 3,
    elevation: 2,
  },
  iconWrap: {
    width: 44, height: 44, borderRadius: 10,
    backgroundColor: `${Colors.accent}20`,
    alignItems: 'center', justifyContent: 'center',
  },
  cardTitle: { fontSize: 15, fontWeight: '700', color: Colors.text },
  cardSub: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
});
