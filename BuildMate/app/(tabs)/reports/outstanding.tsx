import React, { useCallback, useEffect, useState } from 'react';
import {
  View, Text, ScrollView, StyleSheet, TouchableOpacity,
  RefreshControl, ActivityIndicator, TextInput,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { router, useFocusEffect } from 'expo-router';
import { Badge } from '@/components/ui/Badge';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type ReportRow = {
  project_id: number; project_name: string; project_number: string;
  client_name: string | null; status: string;
  budget: number; purchases: number; expenses: number; total_cost: number;
  payments: number; outstanding: number; net_balance: number;
};
type Totals = { budget: number; total_cost: number; payments: number; outstanding: number; net_balance: number };

function fmtINR(n: number): string {
  return '₹' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function KpiStrip({ label, value, color, bg }: { label: string; value: string; color: string; bg: string }) {
  return (
    <View style={[kpi.card, { backgroundColor: bg, borderColor: color + '40' }]}>
      <Text style={kpi.label}>{label}</Text>
      <Text style={[kpi.value, { color }]}>{value}</Text>
    </View>
  );
}

export default function OutstandingReportScreen() {
  const [rows, setRows] = useState<ReportRow[]>([]);
  const [totals, setTotals] = useState<Totals | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');

  const load = useCallback(async (q = '') => {
    try {
      const params: Record<string, string> = {};
      if (q.trim()) params.search = q.trim();
      const res = await api.get('/mobile/reports/outstanding', { params });
      setRows(res.data?.rows ?? []);
      setTotals(res.data?.totals ?? null);
    } catch { /* ignore */ } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);
  useFocusEffect(useCallback(() => { load(search); }, [load, search]));

  function handleSearch(text: string) { setSearch(text); load(text); }

  const filtered = rows.filter((r) => {
    if (!search.trim()) return true;
    const q = search.toLowerCase();
    return r.project_name.toLowerCase().includes(q) ||
      r.project_number.toLowerCase().includes(q) ||
      (r.client_name ?? '').toLowerCase().includes(q);
  });

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      {/* Search bar */}
      <View style={s.searchBar}>
        <Ionicons name="search-outline" size={16} color={Colors.textMuted} style={{ marginRight: 8 }} />
        <TextInput style={s.searchInput} value={search} onChangeText={handleSearch} placeholder="Search projects…" placeholderTextColor={Colors.textMuted} returnKeyType="search" />
        {search.length > 0 && <TouchableOpacity onPress={() => handleSearch('')}><Ionicons name="close-circle" size={16} color={Colors.textMuted} /></TouchableOpacity>}
      </View>

      <ScrollView
        contentContainerStyle={s.content}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(search); }} tintColor={Colors.accent} />}
        showsVerticalScrollIndicator={false}
      >
        {/* KPI Totals */}
        {totals && (
          <>
            <Text style={s.sectionLabel}>Summary — {rows.length} Project{rows.length !== 1 ? 's' : ''}</Text>
            <View style={s.kpiGrid}>
              <KpiStrip label="Budget" value={fmtINR(totals.budget)} color="#2563EB" bg="#EFF6FF" />
              <KpiStrip label="Total Cost" value={fmtINR(totals.total_cost)} color="#B45309" bg="#FFFBEB" />
            </View>
            <View style={s.kpiGrid}>
              <KpiStrip label="Collection" value={fmtINR(totals.payments)} color="#15803D" bg="#F0FDF4" />
              <KpiStrip label="Outstanding" value={fmtINR(totals.outstanding)} color={totals.outstanding > 0 ? Colors.danger : Colors.success} bg={totals.outstanding > 0 ? '#FEF2F2' : '#F0FDF4'} />
            </View>
            <View style={[s.netBalanceCard, { backgroundColor: totals.net_balance >= 0 ? '#F0FDF4' : '#FEF2F2', borderColor: totals.net_balance >= 0 ? '#BBF7D0' : '#FECACA' }]}>
              <Text style={s.netLabel}>Net Balance (Collection − Cost)</Text>
              <Text style={[s.netValue, { color: totals.net_balance >= 0 ? Colors.success : Colors.danger }]}>
                {totals.net_balance >= 0 ? '+' : '−'}{fmtINR(totals.net_balance)}
              </Text>
            </View>
          </>
        )}

        {/* Project rows */}
        <Text style={[s.sectionLabel, { marginTop: 16 }]}>By Project</Text>
        {filtered.length === 0 ? (
          <View style={s.emptyBox}>
            <Ionicons name="bar-chart-outline" size={36} color={Colors.textMuted} />
            <Text style={s.emptyText}>No projects found</Text>
          </View>
        ) : (
          filtered.map((row) => (
            <TouchableOpacity key={row.project_id} style={s.projectCard} onPress={() => router.push(`/(tabs)/projects/${row.project_id}`)} activeOpacity={0.8}>
              <View style={s.projectCardHeader}>
                <View style={{ flex: 1 }}>
                  <Text style={s.projectName} numberOfLines={1}>{row.project_name}</Text>
                  <Text style={s.projectMeta}>{row.project_number}{row.client_name ? ` · ${row.client_name}` : ''}</Text>
                </View>
                <Badge status={row.status} />
              </View>

              <View style={s.finGrid}>
                <View style={s.finCell}>
                  <Text style={s.finCellLabel}>Budget</Text>
                  <Text style={s.finCellValue}>{fmtINR(row.budget)}</Text>
                </View>
                <View style={s.finCell}>
                  <Text style={s.finCellLabel}>Purchases</Text>
                  <Text style={s.finCellValue}>{fmtINR(row.purchases)}</Text>
                </View>
                <View style={s.finCell}>
                  <Text style={s.finCellLabel}>Expenses</Text>
                  <Text style={s.finCellValue}>{fmtINR(row.expenses)}</Text>
                </View>
                <View style={s.finCell}>
                  <Text style={s.finCellLabel}>Collection</Text>
                  <Text style={[s.finCellValue, { color: Colors.success }]}>{fmtINR(row.payments)}</Text>
                </View>
              </View>

              <View style={[s.outstandingRow, { backgroundColor: row.outstanding > 0 ? '#FEF2F2' : '#F0FDF4' }]}>
                <Text style={s.outstandingLabel}>Balance Due</Text>
                <Text style={[s.outstandingValue, { color: row.outstanding > 0 ? Colors.danger : Colors.success }]}>
                  {row.outstanding > 0 ? fmtINR(row.outstanding) : 'Fully Collected'}
                </Text>
              </View>
            </TouchableOpacity>
          ))
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const kpi = StyleSheet.create({
  card: { flex: 1, borderWidth: 1.5, borderRadius: 10, padding: 12 },
  label: { fontSize: 11, fontWeight: '600', color: Colors.textSecondary, textTransform: 'uppercase', letterSpacing: 0.4 },
  value: { fontSize: 16, fontWeight: '800', marginTop: 4 },
});

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  searchBar: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 10, backgroundColor: Colors.white, borderBottomWidth: 1, borderBottomColor: Colors.border },
  searchInput: { flex: 1, fontSize: 14, color: Colors.text },
  content: { padding: 16, paddingBottom: 40 },
  sectionLabel: { fontSize: 11, fontWeight: '700', color: Colors.textSecondary, textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 8 },
  kpiGrid: { flexDirection: 'row', gap: 8, marginBottom: 8 },
  netBalanceCard: { borderWidth: 1.5, borderRadius: 12, padding: 14, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  netLabel: { fontSize: 13, fontWeight: '600', color: Colors.textSecondary },
  netValue: { fontSize: 18, fontWeight: '800' },
  emptyBox: { alignItems: 'center', paddingVertical: 40, gap: 8 },
  emptyText: { fontSize: 14, color: Colors.textMuted },
  projectCard: { backgroundColor: Colors.white, borderRadius: 12, marginBottom: 12, overflow: 'hidden', borderWidth: 1, borderColor: Colors.border },
  projectCardHeader: { flexDirection: 'row', alignItems: 'flex-start', padding: 14, borderBottomWidth: 1, borderBottomColor: Colors.border },
  projectName: { fontSize: 15, fontWeight: '700', color: Colors.text },
  projectMeta: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
  finGrid: { flexDirection: 'row', flexWrap: 'wrap', padding: 8 },
  finCell: { width: '50%', padding: 6 },
  finCellLabel: { fontSize: 10, fontWeight: '600', color: Colors.textMuted, textTransform: 'uppercase', letterSpacing: 0.3 },
  finCellValue: { fontSize: 13, fontWeight: '700', color: Colors.text, marginTop: 2 },
  outstandingRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 14, paddingVertical: 10 },
  outstandingLabel: { fontSize: 12, fontWeight: '600', color: Colors.textSecondary },
  outstandingValue: { fontSize: 14, fontWeight: '800' },
});
