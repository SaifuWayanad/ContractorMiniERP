import React, { useCallback, useEffect, useState } from 'react';
import {
  View, Text, FlatList, StyleSheet, TouchableOpacity,
  TextInput, RefreshControl, ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { router, useFocusEffect } from 'expo-router';
import { Badge } from '@/components/ui/Badge';
import { EmptyState } from '@/components/ui/EmptyState';
import { ScreenHeader } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type Contractor = {
  id: number;
  company_name: string;
  contact_person: string | null;
  email: string | null;
  phone: string | null;
  status: string;
  users_count: number;
  projects_count: number;
};

export default function AdminContractorsScreen() {
  const [contractors, setContractors] = useState<Contractor[]>([]);
  const [filtered, setFiltered] = useState<Contractor[]>([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    try {
      const res = await api.get('/mobile/admin/contractors');
      setContractors(res.data);
    } catch { /* ignore */ } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  useEffect(() => {
    const q = search.toLowerCase();
    setFiltered(q ? contractors.filter((c) =>
      c.company_name.toLowerCase().includes(q) ||
      (c.contact_person ?? '').toLowerCase().includes(q)
    ) : contractors);
  }, [search, contractors]);

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader title="Contractors" subtitle="All contractor accounts" />

      <TouchableOpacity style={s.fab} onPress={() => router.push('/(tabs)/admin/contractors/form')} activeOpacity={0.85}>
        <Ionicons name="add" size={28} color={Colors.primary} />
      </TouchableOpacity>

      <View style={s.searchBar}>
        <Ionicons name="search" size={18} color={Colors.textMuted} style={{ marginRight: 8 }} />
        <TextInput
          style={s.searchInput}
          value={search}
          onChangeText={setSearch}
          placeholder="Search contractors…"
          placeholderTextColor={Colors.textMuted}
          clearButtonMode="while-editing"
        />
      </View>

      <FlatList
        data={filtered}
        keyExtractor={(item) => String(item.id)}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(); }} tintColor={Colors.accent} />}
        contentContainerStyle={filtered.length === 0 ? { flex: 1 } : s.list}
        ListEmptyComponent={<EmptyState icon="business-outline" title="No contractors found" message="Contractors you add appear here." />}
        ItemSeparatorComponent={() => <View style={{ height: 8 }} />}
        renderItem={({ item }) => (
          <TouchableOpacity style={s.row} onPress={() => router.push(`/(tabs)/admin/contractors/${item.id}`)} activeOpacity={0.75}>
            <View style={s.avatar}>
              <Text style={s.avatarText}>{item.company_name.charAt(0).toUpperCase()}</Text>
            </View>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <View style={s.rowTop}>
                <Text style={s.name} numberOfLines={1}>{item.company_name}</Text>
                <Badge status={item.status} />
              </View>
              {item.contact_person && <Text style={s.meta}>{item.contact_person}</Text>}
              <Text style={s.meta}>{item.users_count} user{item.users_count !== 1 ? 's' : ''} · {item.projects_count} project{item.projects_count !== 1 ? 's' : ''}</Text>
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
  fab: {
    position: 'absolute', bottom: 24, right: 20, zIndex: 10,
    width: 56, height: 56, borderRadius: 28,
    backgroundColor: Colors.accent, alignItems: 'center', justifyContent: 'center',
    shadowColor: Colors.accent, shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.4, shadowRadius: 8, elevation: 8,
  },
  searchBar: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: Colors.white, margin: 12, paddingHorizontal: 12,
    borderRadius: 10, height: 42,
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.08, shadowRadius: 3, elevation: 2,
  },
  searchInput: { flex: 1, fontSize: 15, color: Colors.text },
  list: { padding: 12, paddingBottom: 100 },
  row: {
    backgroundColor: Colors.white, borderRadius: 12, padding: 14,
    flexDirection: 'row', alignItems: 'center',
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.08, shadowRadius: 3, elevation: 2,
  },
  avatar: {
    width: 44, height: 44, borderRadius: 22,
    backgroundColor: Colors.primary, alignItems: 'center', justifyContent: 'center',
  },
  avatarText: { fontSize: 18, fontWeight: '800', color: Colors.white },
  rowTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 2 },
  name: { fontSize: 15, fontWeight: '700', color: Colors.text, flex: 1, marginRight: 8 },
  meta: { fontSize: 12, color: Colors.textMuted, marginTop: 1 },
});
