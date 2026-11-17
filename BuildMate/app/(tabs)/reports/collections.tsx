import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  View, Text, ScrollView, StyleSheet, TouchableOpacity,
  RefreshControl, ActivityIndicator, TextInput, Modal, Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from 'expo-router';
import DateTimePicker from '@react-native-community/datetimepicker';
import { SelectPicker, PickerOption } from '@/components/ui/SelectPicker';
import { ScreenHeader } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

const PAYMENT_METHODS: PickerOption[] = [
  { label: 'Cash', value: 'cash' },
  { label: 'Bank Transfer', value: 'bank_transfer' },
  { label: 'UPI', value: 'upi' },
  { label: 'Cheque', value: 'cheque' },
  { label: 'Credit Card', value: 'credit_card' },
  { label: 'Other', value: 'other' },
];

const METHOD_LABELS: Record<string, string> = {
  cash: 'Cash',
  bank_transfer: 'Bank Transfer',
  upi: 'UPI',
  cheque: 'Cheque',
  credit_card: 'Credit Card',
  other: 'Other',
};

type CollectionRow = {
  project_id: number;
  project_name: string;
  project_number: string;
  client_name: string | null;
  [method: string]: number | string | null;
  total: number;
};

type CollectionTotals = {
  [method: string]: number;
  total: number;
};

type ReportData = {
  rows: CollectionRow[];
  totals: CollectionTotals;
  payment_methods: string[];
};

function fmtINR(n: number): string {
  if (n === 0) return '—';
  return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function fmtINRZero(n: number): string {
  return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function fmtDateDisplay(iso: string): string {
  if (!iso) return '';
  const d = new Date(iso + 'T00:00:00Z');
  return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' });
}

function DateFilterField({ label, value, onChange }: { label: string; value: string; onChange: (v: string) => void }) {
  const [show, setShow] = useState(false);
  const date = value ? new Date(value + 'T00:00:00Z') : new Date();

  function onChangeNative(_: any, sel?: Date) {
    if (Platform.OS === 'android') setShow(false);
    if (sel) onChange(sel.toISOString().slice(0, 10));
  }

  return (
    <View style={{ flex: 1 }}>
      <Text style={f.filterLabel}>{label}</Text>
      <TouchableOpacity style={f.dateBtn} onPress={() => setShow(true)} activeOpacity={0.7}>
        <Ionicons name="calendar-outline" size={14} color={value ? Colors.text : Colors.textMuted} style={{ marginRight: 6 }} />
        <Text style={[f.dateBtnText, !value && { color: Colors.textMuted }]} numberOfLines={1}>
          {value ? fmtDateDisplay(value) : 'Any'}
        </Text>
        {value ? (
          <TouchableOpacity onPress={() => onChange('')} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }} style={{ marginLeft: 'auto' }}>
            <Ionicons name="close-circle" size={14} color={Colors.textMuted} />
          </TouchableOpacity>
        ) : null}
      </TouchableOpacity>
      {Platform.OS === 'android' && show && <DateTimePicker mode="date" value={date} onChange={onChangeNative} />}
      {Platform.OS === 'ios' && show && (
        <Modal visible transparent animationType="slide" onRequestClose={() => setShow(false)}>
          <View style={f.overlay}>
            <View style={f.pickerCard}>
              <View style={f.pickerHeader}>
                <TouchableOpacity onPress={() => setShow(false)}><Text style={f.pickerCancel}>Cancel</Text></TouchableOpacity>
                <Text style={f.pickerTitle}>{label}</Text>
                <TouchableOpacity onPress={() => setShow(false)}><Text style={f.pickerDone}>Done</Text></TouchableOpacity>
              </View>
              <DateTimePicker mode="date" value={date} onChange={onChangeNative} display="spinner" style={{ height: 200 }} />
            </View>
          </View>
        </Modal>
      )}
    </View>
  );
}

export default function CollectionReportScreen() {
  const [data, setData] = useState<ReportData | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [filtersVisible, setFiltersVisible] = useState(false);

  // Filter state
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo]     = useState('');
  const [projectId, setProjectId]     = useState('');
  const [clientId, setClientId]       = useState('');
  const [paymentMethod, setPaymentMethod] = useState('');

  // Picker options
  const [projects, setProjects] = useState<PickerOption[]>([]);
  const [clients, setClients]   = useState<PickerOption[]>([]);

  const pendingRef = useRef({ dateFrom, dateTo, projectId, clientId, paymentMethod });

  const load = useCallback(async (filters?: {
    dateFrom: string; dateTo: string; projectId: string; clientId: string; paymentMethod: string;
  }) => {
    const f = filters ?? { dateFrom, dateTo, projectId, clientId, paymentMethod };
    try {
      const params: Record<string, string> = {};
      if (f.dateFrom) params.date_from = f.dateFrom;
      if (f.dateTo)   params.date_to   = f.dateTo;
      if (f.projectId) params.project_id = f.projectId;
      if (f.clientId)  params.client_id  = f.clientId;
      if (f.paymentMethod) params.payment_method = f.paymentMethod;
      const res = await api.get('/mobile/reports/collections', { params });
      setData(res.data ?? null);
    } catch { /* keep previous data */ } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [dateFrom, dateTo, projectId, clientId, paymentMethod]);

  useEffect(() => {
    api.get('/mobile/projects').then((r) => {
      const list = r.data?.data ?? r.data ?? [];
      setProjects(list.map((p: { id: number; name: string; project_number: string }) => ({
        label: `${p.project_number} – ${p.name}`, value: String(p.id),
      })));
    }).catch(() => {});
    api.get('/mobile/clients').then((r) => {
      const list = r.data?.data ?? r.data ?? [];
      setClients(list.map((c: { id: number; name: string }) => ({ label: c.name, value: String(c.id) })));
    }).catch(() => {});
    load();
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  function applyFilters() {
    setFiltersVisible(false);
    load({ dateFrom, dateTo, projectId, clientId, paymentMethod });
  }

  function resetFilters() {
    setDateFrom(''); setDateTo(''); setProjectId(''); setClientId(''); setPaymentMethod('');
    setFiltersVisible(false);
    load({ dateFrom: '', dateTo: '', projectId: '', clientId: '', paymentMethod: '' });
  }

  const hasActiveFilter = !!(dateFrom || dateTo || projectId || clientId || paymentMethod);
  const methods = data?.payment_methods ?? PAYMENT_METHODS.map((m) => m.value);
  const rows = data?.rows ?? [];
  const totals = data?.totals ?? null;

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader
        title="Collection Report"
        subtitle="Project-wise collection by payment method"
        right={
          <TouchableOpacity
            style={[s.filterBtn, hasActiveFilter && s.filterBtnActive]}
            onPress={() => setFiltersVisible(true)}
            activeOpacity={0.8}
          >
            <Ionicons name="options-outline" size={18} color={hasActiveFilter ? Colors.white : Colors.primary} />
            {hasActiveFilter && <View style={s.filterDot} />}
          </TouchableOpacity>
        }
      />

      <ScrollView
        contentContainerStyle={s.content}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(); }} tintColor={Colors.accent} />}
        showsVerticalScrollIndicator={false}
      >
        {/* Active filter chips */}
        {hasActiveFilter && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={s.chipRow} contentContainerStyle={{ gap: 6, paddingHorizontal: 16, paddingVertical: 8 }}>
            {dateFrom && <View style={s.chip}><Text style={s.chipText}>From: {fmtDateDisplay(dateFrom)}</Text></View>}
            {dateTo   && <View style={s.chip}><Text style={s.chipText}>To: {fmtDateDisplay(dateTo)}</Text></View>}
            {projectId && <View style={s.chip}><Text style={s.chipText}>Project filter active</Text></View>}
            {clientId  && <View style={s.chip}><Text style={s.chipText}>Customer filter active</Text></View>}
            {paymentMethod && <View style={s.chip}><Text style={s.chipText}>{METHOD_LABELS[paymentMethod] ?? paymentMethod}</Text></View>}
            <TouchableOpacity style={[s.chip, s.chipReset]} onPress={resetFilters}>
              <Ionicons name="close" size={12} color={Colors.danger} />
              <Text style={[s.chipText, { color: Colors.danger }]}>Reset</Text>
            </TouchableOpacity>
          </ScrollView>
        )}

        {/* Grand total summary cards */}
        {totals && (
          <>
            <Text style={s.sectionLabel}>Summary — {rows.length} Project{rows.length !== 1 ? 's' : ''}</Text>
            <View style={s.totalCard}>
              <Text style={s.totalCardLabel}>Total Collections</Text>
              <Text style={s.totalCardValue}>{fmtINRZero(totals.total)}</Text>
            </View>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={s.methodRow} contentContainerStyle={{ gap: 8, paddingBottom: 4 }}>
              {methods.map((m) => (
                <View key={m} style={s.methodCard}>
                  <Text style={s.methodCardLabel}>{METHOD_LABELS[m] ?? m}</Text>
                  <Text style={s.methodCardValue}>{fmtINRZero((totals[m] as number) ?? 0)}</Text>
                </View>
              ))}
            </ScrollView>
          </>
        )}

        {/* Project table */}
        <Text style={[s.sectionLabel, { marginTop: 16 }]}>By Project</Text>

        {rows.length === 0 ? (
          <View style={s.emptyBox}>
            <Ionicons name="cash-outline" size={36} color={Colors.textMuted} />
            <Text style={s.emptyText}>No collections found</Text>
            {hasActiveFilter && <Text style={s.emptySubText}>Try adjusting your filters</Text>}
          </View>
        ) : (
          <>
            {/* Horizontally scrollable table */}
            <View style={s.tableWrap}>
              <ScrollView horizontal showsHorizontalScrollIndicator style={{ flexShrink: 1 }}>
                <View>
                  {/* Header row */}
                  <View style={[s.tableRow, s.tableHeader]}>
                    <View style={[s.colProject, s.colHeader]}>
                      <Text style={s.headerText}>Project / Customer</Text>
                    </View>
                    {methods.map((m) => (
                      <View key={m} style={[s.colMethod, s.colHeader]}>
                        <Text style={s.headerText} numberOfLines={2}>{METHOD_LABELS[m] ?? m}</Text>
                      </View>
                    ))}
                    <View style={[s.colTotal, s.colHeader]}>
                      <Text style={[s.headerText, { color: Colors.success }]}>Total</Text>
                    </View>
                  </View>

                  {/* Data rows */}
                  {rows.map((row, i) => (
                    <View key={row.project_id} style={[s.tableRow, i % 2 === 1 && s.tableRowAlt]}>
                      <View style={s.colProject}>
                        <Text style={s.projectNameCell} numberOfLines={2}>{row.project_name}</Text>
                        <Text style={s.projectSubCell}>{row.project_number}</Text>
                        {row.client_name ? <Text style={s.clientNameCell} numberOfLines={1}>{row.client_name}</Text> : null}
                      </View>
                      {methods.map((m) => (
                        <View key={m} style={s.colMethod}>
                          <Text style={s.amountCell}>{fmtINR((row[m] as number) ?? 0)}</Text>
                        </View>
                      ))}
                      <View style={s.colTotal}>
                        <Text style={s.totalCell}>{fmtINRZero(row.total)}</Text>
                      </View>
                    </View>
                  ))}

                  {/* Grand Total row */}
                  {totals && (
                    <View style={[s.tableRow, s.grandTotalRow]}>
                      <View style={s.colProject}>
                        <Text style={s.grandTotalLabel}>Grand Total</Text>
                      </View>
                      {methods.map((m) => (
                        <View key={m} style={s.colMethod}>
                          <Text style={s.grandTotalAmount}>{fmtINRZero((totals[m] as number) ?? 0)}</Text>
                        </View>
                      ))}
                      <View style={s.colTotal}>
                        <Text style={[s.grandTotalAmount, { color: Colors.success }]}>{fmtINRZero(totals.total)}</Text>
                      </View>
                    </View>
                  )}
                </View>
              </ScrollView>
            </View>
          </>
        )}
      </ScrollView>

      {/* Filter Modal */}
      <Modal visible={filtersVisible} animationType="slide" presentationStyle="pageSheet" onRequestClose={() => setFiltersVisible(false)}>
        <SafeAreaView style={s.modalSafe} edges={['top', 'bottom']}>
          <View style={s.modalHeader}>
            <TouchableOpacity onPress={() => setFiltersVisible(false)}>
              <Ionicons name="close" size={22} color={Colors.text} />
            </TouchableOpacity>
            <Text style={s.modalTitle}>Filters</Text>
            <TouchableOpacity onPress={resetFilters}>
              <Text style={s.modalReset}>Reset</Text>
            </TouchableOpacity>
          </View>

          <ScrollView contentContainerStyle={s.modalBody} keyboardShouldPersistTaps="handled">
            <Text style={s.filterSectionLabel}>Date Range</Text>
            <View style={{ flexDirection: 'row', gap: 10, marginBottom: 14 }}>
              <DateFilterField label="From" value={dateFrom} onChange={setDateFrom} />
              <DateFilterField label="To"   value={dateTo}   onChange={setDateTo} />
            </View>

            <SelectPicker
              label="Project"
              value={projectId}
              options={projects}
              onChange={setProjectId}
              placeholder="All projects"
              searchable
            />

            <SelectPicker
              label="Customer"
              value={clientId}
              options={clients}
              onChange={setClientId}
              placeholder="All customers"
              searchable
            />

            <SelectPicker
              label="Payment Method"
              value={paymentMethod}
              options={PAYMENT_METHODS}
              onChange={setPaymentMethod}
              placeholder="All methods"
            />

            <TouchableOpacity style={s.applyBtn} onPress={applyFilters} activeOpacity={0.85}>
              <Text style={s.applyBtnText}>Apply Filters</Text>
            </TouchableOpacity>
          </ScrollView>
        </SafeAreaView>
      </Modal>
    </SafeAreaView>
  );
}

const COL_PROJECT_W = 160;
const COL_METHOD_W  = 90;
const COL_TOTAL_W   = 90;

const s = StyleSheet.create({
  safe:     { flex: 1, backgroundColor: Colors.background },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  content:  { paddingBottom: 40 },

  filterBtn: { width: 36, height: 36, borderRadius: 18, backgroundColor: Colors.accent, alignItems: 'center', justifyContent: 'center' },
  filterBtnActive: { backgroundColor: Colors.primary },
  filterDot: { position: 'absolute', top: 6, right: 6, width: 7, height: 7, borderRadius: 4, backgroundColor: Colors.danger, borderWidth: 1.5, borderColor: Colors.white },

  chipRow: { backgroundColor: Colors.white, borderBottomWidth: 1, borderBottomColor: Colors.border },
  chip: { flexDirection: 'row', alignItems: 'center', gap: 4, backgroundColor: Colors.accent + '18', borderRadius: 20, paddingHorizontal: 10, paddingVertical: 4 },
  chipReset: { backgroundColor: Colors.dangerBg },
  chipText: { fontSize: 11, fontWeight: '600', color: Colors.accent },

  sectionLabel: { fontSize: 11, fontWeight: '700', color: Colors.textSecondary, textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 8, paddingHorizontal: 16 },

  totalCard: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: Colors.success + '15', borderRadius: 12, marginHorizontal: 16, padding: 14, marginBottom: 10, borderWidth: 1, borderColor: Colors.success + '30' },
  totalCardLabel: { fontSize: 13, fontWeight: '600', color: Colors.textSecondary },
  totalCardValue: { fontSize: 20, fontWeight: '800', color: Colors.success },

  methodRow: { paddingHorizontal: 16, marginBottom: 4 },
  methodCard: { backgroundColor: Colors.white, borderRadius: 10, padding: 10, borderWidth: 1, borderColor: Colors.border, minWidth: 90, alignItems: 'center' },
  methodCardLabel: { fontSize: 10, fontWeight: '600', color: Colors.textMuted, textTransform: 'uppercase', letterSpacing: 0.3, marginBottom: 4 },
  methodCardValue: { fontSize: 13, fontWeight: '700', color: Colors.text },

  tableWrap: { marginHorizontal: 0, backgroundColor: Colors.white, borderTopWidth: 1, borderBottomWidth: 1, borderColor: Colors.border },

  tableRow: { flexDirection: 'row', borderBottomWidth: 1, borderBottomColor: Colors.border },
  tableRowAlt: { backgroundColor: '#F9FAFB' },
  tableHeader: { backgroundColor: Colors.primary },
  grandTotalRow: { backgroundColor: Colors.primary + '12' },

  colProject: { width: COL_PROJECT_W, paddingHorizontal: 10, paddingVertical: 10, justifyContent: 'center' },
  colMethod:  { width: COL_METHOD_W, paddingHorizontal: 6, paddingVertical: 10, alignItems: 'flex-end', justifyContent: 'center' },
  colTotal:   { width: COL_TOTAL_W, paddingHorizontal: 10, paddingVertical: 10, alignItems: 'flex-end', justifyContent: 'center' },
  colHeader:  {},

  headerText:      { fontSize: 10, fontWeight: '700', color: Colors.white, textTransform: 'uppercase', letterSpacing: 0.3 },
  projectNameCell: { fontSize: 12, fontWeight: '700', color: Colors.text, lineHeight: 16 },
  projectSubCell:  { fontSize: 10, color: Colors.textMuted, marginTop: 2 },
  clientNameCell:  { fontSize: 11, fontWeight: '600', color: Colors.info, marginTop: 2 },
  amountCell:      { fontSize: 12, fontWeight: '600', color: Colors.text, textAlign: 'right' },
  totalCell:       { fontSize: 12, fontWeight: '800', color: Colors.success, textAlign: 'right' },
  grandTotalLabel: { fontSize: 12, fontWeight: '800', color: Colors.primary },
  grandTotalAmount: { fontSize: 12, fontWeight: '800', color: Colors.text, textAlign: 'right' },

  emptyBox:    { alignItems: 'center', paddingVertical: 48, paddingHorizontal: 24, gap: 8 },
  emptyText:   { fontSize: 15, fontWeight: '700', color: Colors.textMuted },
  emptySubText: { fontSize: 13, color: Colors.textMuted },

  // Modal
  modalSafe:   { flex: 1, backgroundColor: Colors.background },
  modalHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 16, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: Colors.border, backgroundColor: Colors.white },
  modalTitle:  { fontSize: 16, fontWeight: '800', color: Colors.text },
  modalReset:  { fontSize: 14, fontWeight: '700', color: Colors.danger },
  modalBody:   { padding: 16, paddingBottom: 40 },
  filterSectionLabel: { fontSize: 12, fontWeight: '700', color: Colors.textSecondary, textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 10 },
  applyBtn:    { backgroundColor: Colors.accent, borderRadius: 12, height: 50, alignItems: 'center', justifyContent: 'center', marginTop: 16 },
  applyBtnText: { fontSize: 16, fontWeight: '800', color: Colors.primary },
});

const f = StyleSheet.create({
  filterLabel:  { fontSize: 12, fontWeight: '600', color: Colors.text, marginBottom: 6 },
  dateBtn:      { flexDirection: 'row', alignItems: 'center', borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10, backgroundColor: Colors.white, paddingHorizontal: 10, height: 44 },
  dateBtnText:  { fontSize: 13, color: Colors.text, flex: 1 },
  overlay:      { flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.35)' },
  pickerCard:   { backgroundColor: Colors.white, borderTopLeftRadius: 16, borderTopRightRadius: 16, paddingBottom: 32 },
  pickerHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 16, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: Colors.border },
  pickerTitle:  { fontSize: 15, fontWeight: '700', color: Colors.text },
  pickerCancel: { fontSize: 15, color: Colors.textSecondary },
  pickerDone:   { fontSize: 15, fontWeight: '700', color: Colors.accent },
});
