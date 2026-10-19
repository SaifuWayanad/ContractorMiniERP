import React, { useCallback, useState } from 'react';
import {
  View, Text, FlatList, StyleSheet, TouchableOpacity,
  RefreshControl, ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { router, useFocusEffect } from 'expo-router';
import { EmptyState } from '@/components/ui/EmptyState';
import { ScreenHeader } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type Role = {
  id: number;
  name: string;
  type: string;
  is_system: boolean;
  users_count: number;
  permissions_count: number;
};

const TYPE_LABEL: Record<string, string> = {
  admin: 'Admin portal',
  contractor: 'Contractor portal',
};

const TYPE_COLOR: Record<string, string> = {
  admin: Colors.primary,
  contractor: Colors.info,
};

export default function AdminRolesScreen() {
  const [roles, setRoles] = useState<Role[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    try {
      const res = await api.get('/mobile/admin/roles');
      setRoles(res.data);
    } catch { /* ignore */ } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader title="Roles" subtitle="Roles & permissions" />
      <FlatList
        data={roles}
        keyExtractor={(item) => String(item.id)}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(); }} tintColor={Colors.accent} />}
        contentContainerStyle={roles.length === 0 ? { flex: 1 } : s.list}
        ListEmptyComponent={<EmptyState icon="shield-outline" title="No roles found" message="Roles appear here." />}
        ItemSeparatorComponent={() => <View style={{ height: 8 }} />}
        renderItem={({ item }) => (
          <TouchableOpacity style={s.row} onPress={() => router.push(`/(tabs)/admin/roles/${item.id}`)} activeOpacity={0.75}>
            <View style={s.iconWrap}>
              <Ionicons name="shield-half" size={22} color={TYPE_COLOR[item.type] ?? Colors.primary} />
            </View>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <View style={s.rowTop}>
                <Text style={s.name} numberOfLines={1}>{item.name}</Text>
                {item.is_system && (
                  <View style={s.systemBadge}>
                    <Text style={s.systemBadgeText}>System</Text>
                  </View>
                )}
              </View>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 4 }}>
                <View style={[s.typeBadge, { backgroundColor: `${TYPE_COLOR[item.type] ?? Colors.primary}15` }]}>
                  <Text style={[s.typeBadgeText, { color: TYPE_COLOR[item.type] ?? Colors.primary }]}>{TYPE_LABEL[item.type] ?? item.type}</Text>
                </View>
                <Text style={s.meta}>{item.permissions_count} perm{item.permissions_count !== 1 ? 's' : ''}</Text>
                <Text style={s.meta}>{item.users_count} user{item.users_count !== 1 ? 's' : ''}</Text>
              </View>
            </View>
            <Ionicons name="chevron-forward" size={16} color={Colors.textMuted} style={{ marginLeft: 4 }} />
          </TouchableOpacity>
        )}
      />
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  list: { padding: 12, paddingBottom: 40 },
  row: {
    backgroundColor: Colors.white, borderRadius: 12, padding: 14,
    flexDirection: 'row', alignItems: 'center',
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.08, shadowRadius: 3, elevation: 2,
  },
  iconWrap: {
    width: 44, height: 44, borderRadius: 10,
    backgroundColor: Colors.background, alignItems: 'center', justifyContent: 'center',
  },
  rowTop: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  name: { fontSize: 15, fontWeight: '700', color: Colors.text, flex: 1 },
  systemBadge: {
    backgroundColor: `${Colors.warning}20`,
    borderRadius: 4, paddingHorizontal: 6, paddingVertical: 2,
  },
  systemBadgeText: { fontSize: 10, fontWeight: '700', color: Colors.warning },
  typeBadge: { borderRadius: 4, paddingHorizontal: 6, paddingVertical: 2 },
  typeBadgeText: { fontSize: 11, fontWeight: '600' },
  meta: { fontSize: 12, color: Colors.textMuted },
});
