import React, { useCallback, useEffect, useState } from 'react';
import {
  View, Text, FlatList, StyleSheet, TouchableOpacity,
  RefreshControl, ActivityIndicator, TextInput, Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { router, useFocusEffect } from 'expo-router';
import { EmptyState } from '@/components/ui/EmptyState';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type Visit = {
  id: number; client_id: number; client_name: string | null;
  meeting_date: string | null; purpose: string | null;
  location: string | null; description: string | null;
  next_meeting_date: string | null;
};

function fmtDate(iso: string | null): string {
  if (!iso) return '—';
  const d = new Date(iso + 'T00:00:00Z');
  return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Kolkata' });
}

export default function VisitsScreen() {
  const [visits, setVisits] = useState<Visit[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');

  const load = useCallback(async (q = '') => {
    try {
      const params: Record<string, string> = {};
      if (q.trim()) params.search = q.trim();
      const res = await api.get('/mobile/visits', { params });
      setVisits(res.data ?? []);
    } catch { /* ignore */ } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);
  useFocusEffect(useCallback(() => { load(search); }, [load, search]));

  function handleSearch(text: string) { setSearch(text); load(text); }

  async function handleDelete(id: number, clientName: string | null) {
    Alert.alert('Delete Visit', `Remove visit with ${clientName ?? 'this client'}?`, [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Delete', style: 'destructive', onPress: async () => {
        try { await api.delete(`/mobile/visits/${id}`); load(search); }
        catch { Alert.alert('Error', 'Failed to delete visit.'); }
      }},
    ]);
  }

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <View style={s.header}>
        <View style={s.searchWrap}>
          <Ionicons name="search-outline" size={16} color={Colors.textMuted} style={{ marginRight: 8 }} />
          <TextInput style={s.searchInput} value={search} onChangeText={handleSearch} placeholder="Search visits…" placeholderTextColor={Colors.textMuted} returnKeyType="search" />
          {search.length > 0 && (
            <TouchableOpacity onPress={() => handleSearch('')}><Ionicons name="close-circle" size={16} color={Colors.textMuted} /></TouchableOpacity>
          )}
        </View>
        <TouchableOpacity style={s.addBtn} onPress={() => router.push('/(tabs)/visits/form' as any)} activeOpacity={0.8}>
          <Ionicons name="add" size={22} color={Colors.primary} />
        </TouchableOpacity>
      </View>

      <FlatList
        data={visits}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={visits.length === 0 ? { flex: 1 } : s.list}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(search); }} tintColor={Colors.accent} />}
        ListEmptyComponent={<EmptyState icon="walk-outline" title="No visits recorded" message="Log your first customer site visit." />}
        renderItem={({ item }) => (
          <TouchableOpacity style={s.row} onPress={() => router.push({ pathname: '/(tabs)/visits/form' as any, params: { id: String(item.id) } })} activeOpacity={0.75}>
            <View style={s.dateBox}>
              <Text style={s.dateDay}>{item.meeting_date ? new Date(item.meeting_date + 'T00:00:00Z').getUTCDate() : '—'}</Text>
              <Text style={s.dateMon}>{item.meeting_date ? new Date(item.meeting_date + 'T00:00:00Z').toLocaleString('en-IN', { month: 'short', timeZone: 'UTC' }) : ''}</Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={s.clientName} numberOfLines={1}>{item.client_name ?? 'Unknown client'}</Text>
              {item.purpose && <Text style={s.purpose} numberOfLines={1}>{item.purpose}</Text>}
              {item.location && <Text style={s.meta} numberOfLines={1}><Ionicons name="location-outline" size={11} color={Colors.textMuted} /> {item.location}</Text>}
              {item.next_meeting_date && <Text style={s.nextMeeting}>Next: {fmtDate(item.next_meeting_date)}</Text>}
            </View>
            <TouchableOpacity onPress={() => handleDelete(item.id, item.client_name)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }} style={{ marginLeft: 8 }}>
              <Ionicons name="trash-outline" size={18} color={Colors.danger} />
            </TouchableOpacity>
          </TouchableOpacity>
        )}
      />
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  list: { paddingBottom: 32 },
  header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 10, gap: 10, backgroundColor: Colors.white, borderBottomWidth: 1, borderBottomColor: Colors.border },
  searchWrap: { flex: 1, flexDirection: 'row', alignItems: 'center', backgroundColor: Colors.background, borderRadius: 10, borderWidth: 1.5, borderColor: Colors.border, paddingHorizontal: 10, height: 40 },
  searchInput: { flex: 1, fontSize: 14, color: Colors.text },
  addBtn: { width: 40, height: 40, borderRadius: 20, backgroundColor: Colors.accent, alignItems: 'center', justifyContent: 'center' },
  row: { flexDirection: 'row', alignItems: 'center', backgroundColor: Colors.white, paddingHorizontal: 16, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: Colors.border },
  dateBox: { alignItems: 'center', backgroundColor: Colors.primary + '12', borderRadius: 8, paddingHorizontal: 8, paddingVertical: 6, marginRight: 12, minWidth: 44 },
  dateDay: { fontSize: 18, fontWeight: '800', color: Colors.primary },
  dateMon: { fontSize: 10, fontWeight: '600', color: Colors.textSecondary },
  clientName: { fontSize: 15, fontWeight: '700', color: Colors.text },
  purpose: { fontSize: 13, color: Colors.textSecondary, marginTop: 2 },
  meta: { fontSize: 11, color: Colors.textMuted, marginTop: 2 },
  nextMeeting: { fontSize: 11, color: Colors.accent, fontWeight: '600', marginTop: 2 },
});
