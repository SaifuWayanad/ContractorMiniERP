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

type PurchaseItemDetail = {
  id: number; project_id: number | null; project_name: string | null; project_number: string | null;
  product_name: string | null; product_unit: string | null; unit: string | null;
  supplier: string | null; quantity: number; unit_amount: number; line_total: number;
};
type Purchase = {
  id: number; project_id: number | null; project_name: string | null; project_number: string | null;
  product_name: string | null; product_unit: string | null;
  purchase_date: string | null; quantity: number | null; unit_amount: number | null;
  line_total: number; total_amount: number | null;
  supplier: string | null; invoice_reference: string | null;
  items: PurchaseItemDetail[];
};

function fmtDate(iso: string | null): string {
  if (!iso) return '—';
  const d = new Date(iso + 'T00:00:00Z');
  return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' });
}
function fmtINR(n: number): string {
  return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export default function PurchasesScreen() {
  const [purchases, setPurchases] = useState<Purchase[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');

  const load = useCallback(async () => {
    try {
      const res = await api.get('/mobile/purchases');
      setPurchases(res.data ?? []);
    } catch { /* ignore */ } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);
  useFocusEffect(useCallback(() => { load(); }, [load]));

  const filtered = purchases.filter((p) => {
    if (!search.trim()) return true;
    const q = search.toLowerCase();
    const inItems = (p.items ?? []).some(
      (it) => (it.product_name ?? '').toLowerCase().includes(q) ||
               (it.project_name ?? '').toLowerCase().includes(q) ||
               (it.supplier ?? '').toLowerCase().includes(q)
    );
    return (p.product_name ?? '').toLowerCase().includes(q) ||
      (p.project_name ?? '').toLowerCase().includes(q) ||
      (p.supplier ?? '').toLowerCase().includes(q) ||
      (p.invoice_reference ?? '').toLowerCase().includes(q) ||
      inItems;
  });

  const total = filtered.reduce((sum, p) => sum + (p.total_amount ?? p.line_total), 0);

  async function handleDelete(id: number) {
    Alert.alert('Delete Purchase', 'Delete this purchase record?', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Delete', style: 'destructive', onPress: async () => {
        try { await api.delete(`/mobile/purchases/${id}`); load(); }
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
          <TextInput style={s.searchInput} value={search} onChangeText={setSearch} placeholder="Search purchases…" placeholderTextColor={Colors.textMuted} />
          {search.length > 0 && <TouchableOpacity onPress={() => setSearch('')}><Ionicons name="close-circle" size={16} color={Colors.textMuted} /></TouchableOpacity>}
        </View>
        <TouchableOpacity style={s.addBtn} onPress={() => router.push('/(tabs)/purchases/form' as any)} activeOpacity={0.8}>
          <Ionicons name="add" size={22} color={Colors.primary} />
        </TouchableOpacity>
      </View>

      {filtered.length > 0 && (
        <View style={s.totalBar}>
          <Text style={s.totalLabel}>{filtered.length} record{filtered.length !== 1 ? 's' : ''}</Text>
          <Text style={s.totalValue}>{fmtINR(total)}</Text>
        </View>
      )}

      <FlatList
        data={filtered}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={filtered.length === 0 ? { flex: 1 } : s.list}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(); }} tintColor={Colors.accent} />}
        ListEmptyComponent={<EmptyState icon="cart-outline" title="No purchases found" message="Add material purchases from within a project or tap + to create a multi-project purchase." />}
        renderItem={({ item }) => {
          const isMulti = item.items && item.items.length > 0;
          const displayTotal = item.total_amount ?? item.line_total;

          if (isMulti) {
            // Determine unique projects in items
            const projectNames = [...new Set(item.items.map((it) => it.project_name).filter(Boolean))];
            return (
              <View style={s.row}>
                <View style={{ flex: 1 }}>
                  <Text style={s.productName} numberOfLines={1}>
                    {item.invoice_reference ? `Ref: ${item.invoice_reference}` : `Purchase #${item.id}`}
                  </Text>
                  {projectNames.length > 0 && (
                    <Text style={s.projectName} numberOfLines={1}>{projectNames.join(', ')}</Text>
                  )}
                  <Text style={s.meta}>{fmtDate(item.purchase_date)} · {item.items.length} item{item.items.length !== 1 ? 's' : ''}</Text>
                  {item.items.map((it) => (
                    <Text key={it.id} style={s.itemLine} numberOfLines={1}>
                      {'· '}{it.product_name ?? 'Product'} — {it.quantity} × {fmtINR(it.unit_amount)}
                    </Text>
                  ))}
                </View>
                <View style={s.rightCol}>
                  <Text style={s.amount}>{fmtINR(displayTotal)}</Text>
                  <TouchableOpacity onPress={() => handleDelete(item.id)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }} style={{ marginTop: 6 }}>
                    <Ionicons name="trash-outline" size={16} color={Colors.danger} />
                  </TouchableOpacity>
                </View>
              </View>
            );
          }

          return (
            <TouchableOpacity style={s.row} onPress={() => router.push({ pathname: '/(tabs)/purchases/form' as any, params: { id: String(item.id) } })} activeOpacity={0.75}>
              <View style={{ flex: 1 }}>
                <Text style={s.productName} numberOfLines={1}>{item.product_name ?? 'Unknown product'}</Text>
                {item.project_name && <Text style={s.projectName} numberOfLines={1}>{item.project_name}</Text>}
                <Text style={s.meta}>{fmtDate(item.purchase_date)}{item.quantity != null ? ` · ${item.quantity} ${item.product_unit ?? ''} × ${fmtINR(item.unit_amount ?? 0)}` : ''}</Text>
                {item.supplier && <Text style={s.supplier} numberOfLines={1}>{item.supplier}</Text>}
              </View>
              <View style={s.rightCol}>
                <Text style={s.amount}>{fmtINR(displayTotal)}</Text>
                <TouchableOpacity onPress={() => handleDelete(item.id)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }} style={{ marginTop: 6 }}>
                  <Ionicons name="trash-outline" size={16} color={Colors.danger} />
                </TouchableOpacity>
              </View>
            </TouchableOpacity>
          );
        }}
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
  totalBar: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 8, backgroundColor: Colors.accent + '15', borderBottomWidth: 1, borderBottomColor: Colors.accent + '30' },
  totalLabel: { fontSize: 12, color: Colors.textSecondary, fontWeight: '600' },
  totalValue: { fontSize: 15, fontWeight: '800', color: Colors.accent },
  row: { flexDirection: 'row', alignItems: 'center', backgroundColor: Colors.white, paddingHorizontal: 16, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: Colors.border },
  productName: { fontSize: 15, fontWeight: '700', color: Colors.text },
  projectName: { fontSize: 12, color: Colors.info, fontWeight: '600', marginTop: 2 },
  meta: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
  supplier: { fontSize: 11, color: Colors.textMuted, marginTop: 2 },
  itemLine: { fontSize: 11, color: Colors.textMuted, marginTop: 2, lineHeight: 16 },
  rightCol: { alignItems: 'flex-end', marginLeft: 12 },
  amount: { fontSize: 15, fontWeight: '800', color: Colors.text },
});
