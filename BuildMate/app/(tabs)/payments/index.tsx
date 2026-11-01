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

type Payment = {
  id: number; project_id: number; project_name: string | null;
  payment_date: string | null; amount_received: number;
  payment_method: string | null; receipt_reference: string | null; notes: string | null;
};

function fmtDate(iso: string | null): string {
  if (!iso) return '—';
  const d = new Date(iso + 'T00:00:00Z');
  return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' });
}
function fmtINR(n: number): string {
  return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export default function PaymentsScreen() {
  const [payments, setPayments] = useState<Payment[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');

  const load = useCallback(async () => {
    try {
      const res = await api.get('/mobile/payments');
      setPayments(res.data ?? []);
    } catch { /* ignore */ } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);
  useFocusEffect(useCallback(() => { load(); }, [load]));

  const filtered = payments.filter((p) => {
    if (!search.trim()) return true;
    const q = search.toLowerCase();
    return (p.project_name ?? '').toLowerCase().includes(q) ||
      (p.payment_method ?? '').toLowerCase().includes(q) ||
      (p.receipt_reference ?? '').toLowerCase().includes(q);
  });

  const total = filtered.reduce((sum, p) => sum + p.amount_received, 0);

  async function handleDelete(id: number) {
    Alert.alert('Delete Payment', 'Delete this payment record?', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Delete', style: 'destructive', onPress: async () => {
        try { await api.delete(`/mobile/payments/${id}`); load(); }
        catch { Alert.alert('Error', 'Failed to delete.'); }
      }},
    ]);
  }

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <View style={s.header}>
        <View style={s.searchWrap}>
          <Ionicons name="search-outline" size={16} color={Colors.textMuted} style={{ marginRight: 8 }} />
          <TextInput style={s.searchInput} value={search} onChangeText={setSearch} placeholder="Search payments…" placeholderTextColor={Colors.textMuted} />
          {search.length > 0 && <TouchableOpacity onPress={() => setSearch('')}><Ionicons name="close-circle" size={16} color={Colors.textMuted} /></TouchableOpacity>}
        </View>
        <TouchableOpacity style={s.addBtn} onPress={() => router.push('/(tabs)/payments/form' as any)} activeOpacity={0.8}>
          <Ionicons name="add" size={22} color={Colors.primary} />
        </TouchableOpacity>
      </View>

      {filtered.length > 0 && (
        <View style={s.totalBar}>
          <Text style={s.totalLabel}>{filtered.length} payment{filtered.length !== 1 ? 's' : ''}</Text>
          <Text style={s.totalValue}>{fmtINR(total)}</Text>
        </View>
      )}

      <FlatList
        data={filtered}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={filtered.length === 0 ? { flex: 1 } : s.list}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(); }} tintColor={Colors.accent} />}
        ListEmptyComponent={<EmptyState icon="cash-outline" title="No payments recorded" message="Record client payments from within a project." />}
        renderItem={({ item }) => (
          <TouchableOpacity style={s.row} onPress={() => router.push({ pathname: '/(tabs)/payments/form' as any, params: { id: String(item.id) } })} activeOpacity={0.75}>
            <View style={s.amountBox}>
              <Text style={s.amountBoxValue}>{fmtINR(item.amount_received)}</Text>
              <Text style={s.amountBoxDate}>{fmtDate(item.payment_date)}</Text>
            </View>
            <View style={{ flex: 1 }}>
              {item.project_name && <Text style={s.projectName} numberOfLines={1}>{item.project_name}</Text>}
              {item.payment_method && <Text style={s.meta}>{item.payment_method}</Text>}
              {item.receipt_reference && <Text style={s.meta}>Ref: {item.receipt_reference}</Text>}
              {item.notes && <Text style={s.notes} numberOfLines={1}>{item.notes}</Text>}
            </View>
            <TouchableOpacity onPress={() => handleDelete(item.id)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }} style={{ marginLeft: 8 }}>
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
  totalBar: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 8, backgroundColor: Colors.success + '15', borderBottomWidth: 1, borderBottomColor: Colors.success + '30' },
  totalLabel: { fontSize: 12, color: Colors.textSecondary, fontWeight: '600' },
  totalValue: { fontSize: 15, fontWeight: '800', color: Colors.success },
  row: { flexDirection: 'row', alignItems: 'center', backgroundColor: Colors.white, paddingHorizontal: 16, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: Colors.border },
  amountBox: { marginRight: 12, alignItems: 'flex-end' },
  amountBoxValue: { fontSize: 16, fontWeight: '800', color: Colors.success },
  amountBoxDate: { fontSize: 11, color: Colors.textSecondary, marginTop: 2 },
  projectName: { fontSize: 14, fontWeight: '700', color: Colors.text },
  meta: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
  notes: { fontSize: 11, color: Colors.textMuted, marginTop: 2, fontStyle: 'italic' },
});
