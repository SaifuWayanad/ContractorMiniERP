import React, { useCallback, useEffect, useState } from 'react';
import { useFocusEffect } from 'expo-router';
import {
  View, Text, FlatList, StyleSheet, TouchableOpacity,
  TextInput, RefreshControl, ActivityIndicator, Linking,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { Badge } from '@/components/ui/Badge';
import { EmptyState } from '@/components/ui/EmptyState';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type Client = {
  id: number;
  name: string;
  company: string | null;
  contact_person: string | null;
  phone: string | null;
  status: string;
  projects_count: number;
};

export default function ClientsScreen() {
  const [clients, setClients] = useState<Client[]>([]);
  const [filtered, setFiltered] = useState<Client[]>([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    try {
      const res = await api.get('/mobile/clients');
      setClients(res.data.data ?? res.data);
    } catch { /* ignore */ } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  useEffect(() => {
    const q = search.toLowerCase();
    setFiltered(q ? clients.filter((c) => c.name.toLowerCase().includes(q) || (c.company ?? '').toLowerCase().includes(q)) : clients);
  }, [search, clients]);

  if (loading) return <View style={styles.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      {/* FAB */}
      <TouchableOpacity style={styles.fab} onPress={() => router.push('/(tabs)/clients/form')} activeOpacity={0.85}>
        <Ionicons name="add" size={28} color={Colors.primary} />
      </TouchableOpacity>

      <View style={styles.searchBar}>
        <Ionicons name="search" size={18} color={Colors.textMuted} style={{ marginRight: 8 }} />
        <TextInput
          style={styles.searchInput}
          value={search}
          onChangeText={setSearch}
          placeholder="Search clients…"
          placeholderTextColor={Colors.textMuted}
          clearButtonMode="while-editing"
        />
      </View>

      <FlatList
        data={filtered}
        keyExtractor={(item) => String(item.id)}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(); }} tintColor={Colors.accent} />}
        contentContainerStyle={filtered.length === 0 ? { flex: 1 } : styles.list}
        ListEmptyComponent={<EmptyState icon="person-circle-outline" title="No customers found" message="Customers you add appear here." />}
        ItemSeparatorComponent={() => <View style={styles.sep} />}
        renderItem={({ item }) => (
          <TouchableOpacity style={styles.row} onPress={() => router.push(`/(tabs)/clients/${item.id}`)} activeOpacity={0.75}>
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>{item.name.charAt(0).toUpperCase()}</Text>
            </View>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <View style={styles.rowTop}>
                <Text style={styles.name} numberOfLines={1}>{item.name}</Text>
                <Badge status={item.status} />
              </View>
              {item.company && <Text style={styles.company} numberOfLines={1}>{item.company}</Text>}
              {item.contact_person && <Text style={styles.meta}>{item.contact_person}</Text>}
              <Text style={styles.meta}>{item.projects_count} project{item.projects_count !== 1 ? 's' : ''}</Text>
            </View>
            {item.phone && (
              <TouchableOpacity onPress={(e) => { e.stopPropagation(); Linking.openURL(`tel:${item.phone}`); }} style={styles.callBtn}>
                <Ionicons name="call-outline" size={18} color={Colors.success} />
              </TouchableOpacity>
            )}
            <Ionicons name="chevron-forward" size={16} color={Colors.textMuted} style={{ marginLeft: 4 }} />
          </TouchableOpacity>
        )}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
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
  sep: { height: 8 },
  avatar: {
    width: 44, height: 44, borderRadius: 22,
    backgroundColor: Colors.primary, alignItems: 'center', justifyContent: 'center',
  },
  avatarText: { fontSize: 18, fontWeight: '800', color: Colors.white },
  rowTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 2 },
  name: { fontSize: 15, fontWeight: '700', color: Colors.text, flex: 1, marginRight: 8 },
  company: { fontSize: 12, color: Colors.textSecondary },
  meta: { fontSize: 12, color: Colors.textMuted, marginTop: 1 },
  callBtn: { padding: 8, marginLeft: 4 },
});
