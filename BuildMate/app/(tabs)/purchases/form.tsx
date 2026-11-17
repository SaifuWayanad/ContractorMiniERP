import React, { useCallback, useEffect, useState } from 'react';
import {
  View, Text, StyleSheet, TextInput, TouchableOpacity,
  ActivityIndicator, Alert, KeyboardAvoidingView, Platform, Modal,
  ScrollView as RNScrollView, FlatList,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, useLocalSearchParams, useFocusEffect } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import DateTimePicker from '@react-native-community/datetimepicker';
import { SelectPicker, PickerOption } from '@/components/ui/SelectPicker';
import { ScreenHeader, HeaderSaveButton } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';
import { goBack } from '@/services/navigation';

// ─── Shared helpers ────────────────────────────────────────────────────────────

function Field({ label, required, children }: { label: string; required?: boolean; children: React.ReactNode }) {
  return (
    <View style={s.field}>
      <Text style={s.label}>{label}{required && <Text style={{ color: Colors.danger }}> *</Text>}</Text>
      {children}
    </View>
  );
}

function Input(props: React.ComponentProps<typeof TextInput> & { multiline?: boolean }) {
  return <TextInput style={[s.input, props.multiline && s.inputMulti]} placeholderTextColor={Colors.textMuted} {...props} />;
}

function fmtINR(n: number) {
  return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function DateField({ label, value, onChange, required }: { label: string; value: string; onChange: (v: string) => void; required?: boolean }) {
  const [show, setShow] = useState(false);
  const date = value ? new Date(value + 'T00:00:00Z') : new Date();
  const display = value
    ? new Date(value + 'T00:00:00Z').toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' })
    : '';

  function onChangeNative(_: any, sel?: Date) {
    if (Platform.OS === 'android') setShow(false);
    if (sel) onChange(sel.toISOString().slice(0, 10));
  }

  return (
    <Field label={label} required={required}>
      <TouchableOpacity style={s.dateBtn} onPress={() => setShow(true)} activeOpacity={0.7}>
        <Ionicons name="calendar-outline" size={18} color={value ? Colors.text : Colors.textMuted} style={{ marginRight: 8 }} />
        <Text style={[s.dateBtnText, !value && { color: Colors.textMuted }]}>{display || 'Select date'}</Text>
        {value
          ? <TouchableOpacity onPress={() => onChange('')} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }} style={{ marginLeft: 'auto' }}><Ionicons name="close-circle" size={18} color={Colors.textMuted} /></TouchableOpacity>
          : <Ionicons name="chevron-down" size={16} color={Colors.textMuted} style={{ marginLeft: 'auto' }} />}
      </TouchableOpacity>
      {Platform.OS === 'android' && show && <DateTimePicker mode="date" value={date} onChange={onChangeNative} />}
      {Platform.OS === 'ios' && (
        <Modal visible={show} transparent animationType="slide" onRequestClose={() => setShow(false)}>
          <View style={s.overlay}><View style={s.pickerCard}>
            <View style={s.pickerHeader}>
              <TouchableOpacity onPress={() => setShow(false)}><Text style={s.pickerCancel}>Cancel</Text></TouchableOpacity>
              <Text style={s.pickerTitle}>{label}</Text>
              <TouchableOpacity onPress={() => setShow(false)}><Text style={s.pickerDone}>Done</Text></TouchableOpacity>
            </View>
            <DateTimePicker mode="date" value={date} onChange={onChangeNative} display="spinner" style={{ height: 200 }} />
          </View></View>
        </Modal>
      )}
    </Field>
  );
}

// ─── Types ──────────────────────────────────────────────────────────────────────

type ProductMeta = { id: number; name: string; unit: string | null; default_rate: number };

type LineItem = {
  key: string;
  projectId: string;
  projectLabel: string;
  productId: string;
  productLabel: string;
  unit: string;
  supplier: string;
  quantity: string;
  unitRate: string;
  lineAmount: string;
  lineAmountManual: boolean; // true when user has overridden the auto-calc
};

let keyCounter = 0;
function newKey() { return `li_${++keyCounter}`; }
function emptyLine(): LineItem {
  return { key: newKey(), projectId: '', projectLabel: '', productId: '', productLabel: '', unit: '', supplier: '', quantity: '', unitRate: '', lineAmount: '', lineAmountManual: false };
}

// ═══════════════════════════════════════════════════════════════════════════════
// CREATE MODE – multi-project, multi-item
// ═══════════════════════════════════════════════════════════════════════════════

function CreatePurchaseScreen() {
  const [projects, setProjects] = useState<PickerOption[]>([]);
  const [products, setProducts] = useState<PickerOption[]>([]);
  const [productMeta, setProductMeta] = useState<Map<string, ProductMeta>>(new Map());
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(true);

  // Header
  const [invoiceRef, setInvoiceRef] = useState('');
  const [purchaseDate, setPurchaseDate] = useState(new Date().toISOString().slice(0, 10));

  // Current entry row state
  const [entryProjectId, setEntryProjectId] = useState('');
  const [entryProductId, setEntryProductId] = useState('');
  const [entryUnit, setEntryUnit] = useState('');
  const [entrySupplier, setEntrySupplier] = useState('');
  const [entryQty, setEntryQty] = useState('');
  const [entryRate, setEntryRate] = useState('');
  const [entryLine, setEntryLine] = useState('');
  const [entryLineManual, setEntryLineManual] = useState(false);

  // Editing an existing line (null = adding new)
  const [editingKey, setEditingKey] = useState<string | null>(null);

  // Added items
  const [items, setItems] = useState<LineItem[]>([]);

  useFocusEffect(useCallback(() => {
    setInvoiceRef(''); setPurchaseDate(new Date().toISOString().slice(0, 10));
    setItems([]); clearEntry();
  }, []));

  useEffect(() => {
    Promise.all([
      api.get('/mobile/projects').then((r) => {
        const data = r.data?.data ?? r.data;
        setProjects((data ?? []).map((p: { id: number; name: string; project_number: string }) => ({
          label: `${p.project_number} – ${p.name}`,
          value: String(p.id),
        })));
      }),
      api.get('/mobile/products').then((r) => {
        const data = r.data?.data ?? r.data;
        const meta = new Map<string, ProductMeta>();
        const opts: PickerOption[] = [];
        for (const p of (data ?? [])) {
          const key = String(p.id);
          meta.set(key, p);
          opts.push({ label: p.name + (p.unit ? ` (${p.unit})` : ''), value: key });
        }
        setProductMeta(meta);
        setProducts(opts);
      }),
    ])
      .catch(() => Alert.alert('Error', 'Failed to load data.'))
      .finally(() => setLoading(false));
  }, []);

  function clearEntry() {
    setEntryProjectId(''); setEntryProductId(''); setEntryUnit(''); setEntrySupplier('');
    setEntryQty(''); setEntryRate(''); setEntryLine(''); setEntryLineManual(false);
    setEditingKey(null);
  }

  // Auto-fill unit and rate when product changes
  function handleProductChange(pid: string) {
    setEntryProductId(pid);
    const meta = productMeta.get(pid);
    if (meta) {
      if (meta.unit) setEntryUnit(meta.unit);
      if (meta.default_rate > 0) {
        const rate = String(meta.default_rate);
        setEntryRate(rate);
        if (!entryLineManual) {
          const qty = parseFloat(entryQty) || 0;
          setEntryLine(qty > 0 ? String(round2(qty * meta.default_rate)) : '');
        }
      }
    }
  }

  function handleQtyChange(v: string) {
    setEntryQty(v);
    if (!entryLineManual) {
      const qty = parseFloat(v) || 0;
      const rate = parseFloat(entryRate) || 0;
      setEntryLine(qty > 0 && rate > 0 ? String(round2(qty * rate)) : '');
    }
  }

  function handleRateChange(v: string) {
    setEntryRate(v);
    if (!entryLineManual) {
      const qty = parseFloat(entryQty) || 0;
      const rate = parseFloat(v) || 0;
      setEntryLine(qty > 0 && rate > 0 ? String(round2(qty * rate)) : '');
    }
  }

  function handleLineAmountChange(v: string) {
    setEntryLine(v);
    setEntryLineManual(true);
  }

  function round2(n: number) { return Math.round(n * 100) / 100; }

  function handleAdd() {
    if (!entryProjectId) { Alert.alert('Required', 'Select a project for this entry.'); return; }
    if (!entryProductId) { Alert.alert('Required', 'Select a product for this entry.'); return; }
    if (!entryUnit.trim()) { Alert.alert('Required', 'Unit is required.'); return; }
    if (!entryQty || parseFloat(entryQty) <= 0) { Alert.alert('Required', 'Enter a valid quantity.'); return; }
    if (!entryRate || parseFloat(entryRate) < 0) { Alert.alert('Required', 'Enter a valid unit rate.'); return; }

    const finalLine = entryLineManual && entryLine
      ? parseFloat(entryLine) >= 0 ? entryLine : String(round2(parseFloat(entryQty) * parseFloat(entryRate)))
      : String(round2((parseFloat(entryQty) || 0) * (parseFloat(entryRate) || 0)));

    const proj = projects.find((p) => p.value === entryProjectId);
    const prod = products.find((p) => p.value === entryProductId);

    const newItem: LineItem = {
      key: editingKey ?? newKey(),
      projectId: entryProjectId,
      projectLabel: proj?.label ?? '',
      productId: entryProductId,
      productLabel: prod?.label ?? '',
      unit: entryUnit.trim(),
      supplier: entrySupplier.trim(),
      quantity: entryQty,
      unitRate: entryRate,
      lineAmount: finalLine,
      lineAmountManual: entryLineManual,
    };

    if (editingKey) {
      setItems((prev) => prev.map((it) => it.key === editingKey ? newItem : it));
    } else {
      setItems((prev) => [...prev, newItem]);
    }
    clearEntry();
  }

  function handleEdit(item: LineItem) {
    setEditingKey(item.key);
    setEntryProjectId(item.projectId);
    setEntryProductId(item.productId);
    setEntryUnit(item.unit);
    setEntrySupplier(item.supplier);
    setEntryQty(item.quantity);
    setEntryRate(item.unitRate);
    setEntryLine(item.lineAmount);
    setEntryLineManual(item.lineAmountManual);
  }

  function handleRemove(key: string) {
    const item = items.find((it) => it.key === key);
    if (!item) return;
    Alert.alert('Remove Entry', `Remove ${item.productLabel} from this purchase?`, [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Remove', style: 'destructive', onPress: () => setItems((prev) => prev.filter((it) => it.key !== key)) },
    ]);
  }

  const grandTotal = items.reduce((sum, it) => sum + (parseFloat(it.lineAmount) || 0), 0);

  async function handleSave() {
    if (!purchaseDate) { Alert.alert('Required', 'Purchase date is required.'); return; }
    if (items.length === 0) { Alert.alert('Required', 'Add at least one purchase entry.'); return; }

    setSaving(true);
    try {
      await api.post('/mobile/purchases/batch', {
        purchase_date: purchaseDate,
        invoice_reference: invoiceRef.trim() || undefined,
        items: items.map((it) => ({
          project_id: parseInt(it.projectId, 10),
          product_id: parseInt(it.productId, 10),
          supplier: it.supplier || undefined,
          unit: it.unit,
          quantity: parseFloat(it.quantity),
          unit_amount: parseFloat(it.unitRate),
        })),
      });

      Alert.alert('Saved', 'Purchase saved successfully.', [{
        text: 'OK',
        onPress: () => router.replace('/(tabs)/purchases' as any),
      }]);
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.message ?? 'Failed to save purchase.');
    } finally {
      setSaving(false);
    }
  }

  const entryLineAuto = String(round2((parseFloat(entryQty) || 0) * (parseFloat(entryRate) || 0)));

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader
        title="New Purchase"
        subtitle="Multi-project purchase entry"
        right={<HeaderSaveButton onPress={handleSave} saving={saving} label="Save" />}
      />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <RNScrollView contentContainerStyle={s.content} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>

          {/* ── Purchase Header ── */}
          <View style={s.card}>
            <Text style={s.cardTitle}>Invoice Details</Text>
            <DateField label="Purchase Date" value={purchaseDate} onChange={setPurchaseDate} required />
            <Field label="Invoice / Reference No.">
              <Input value={invoiceRef} onChangeText={setInvoiceRef} placeholder="e.g. INV-2024-001 (optional)" />
            </Field>
          </View>

          {/* ── Entry Form ── */}
          <View style={s.card}>
            <Text style={s.cardTitle}>{editingKey ? 'Edit Entry' : 'Add Entry'}</Text>
            <Text style={s.cardSub}>Fill in the fields below and tap Add</Text>

            <SelectPicker label="Project" value={entryProjectId} options={projects} onChange={setEntryProjectId} placeholder="Select project…" searchable required />
            <SelectPicker label="Product / Material" value={entryProductId} options={products} onChange={handleProductChange} placeholder="Select product…" searchable required />

            <View style={s.row2}>
              <View style={{ flex: 1, marginRight: 6 }}>
                <Field label="Unit" required>
                  <Input value={entryUnit} onChangeText={setEntryUnit} placeholder="Bags, Kg, Loads…" />
                </Field>
              </View>
              <View style={{ flex: 1.4 }}>
                <Field label="Supplier (Purchase)">
                  <Input value={entrySupplier} onChangeText={setEntrySupplier} placeholder="Supplier name" />
                </Field>
              </View>
            </View>

            <View style={s.row2}>
              <View style={{ flex: 1, marginRight: 6 }}>
                <Field label="Quantity" required>
                  <Input value={entryQty} onChangeText={handleQtyChange} keyboardType="decimal-pad" placeholder="0" />
                </Field>
              </View>
              <View style={{ flex: 1.2 }}>
                <Field label="Unit Rate (₹)" required>
                  <Input value={entryRate} onChangeText={handleRateChange} keyboardType="decimal-pad" placeholder="0.00" />
                </Field>
              </View>
            </View>

            <Field label="Line Amount (₹)" required>
              <View style={{ position: 'relative' }}>
                <Input
                  value={entryLine}
                  onChangeText={handleLineAmountChange}
                  keyboardType="decimal-pad"
                  placeholder={entryLineAuto !== '0' ? entryLineAuto : '0.00'}
                />
                {!entryLineManual && parseFloat(entryLine) > 0 && (
                  <View style={s.autoTag}>
                    <Text style={s.autoTagText}>auto</Text>
                  </View>
                )}
              </View>
              {entryLineManual && parseFloat(entryLine) !== parseFloat(entryLineAuto) && (
                <TouchableOpacity onPress={() => { setEntryLine(entryLineAuto); setEntryLineManual(false); }} style={s.resetCalc}>
                  <Ionicons name="refresh-outline" size={12} color={Colors.accent} />
                  <Text style={s.resetCalcText}>Reset to {fmtINR(parseFloat(entryLineAuto) || 0)}</Text>
                </TouchableOpacity>
              )}
            </Field>

            <TouchableOpacity style={s.addEntryBtn} onPress={handleAdd} activeOpacity={0.85}>
              <Ionicons name={editingKey ? 'checkmark-circle-outline' : 'add-circle-outline'} size={20} color={Colors.primary} />
              <Text style={s.addEntryBtnText}>{editingKey ? 'Update Entry' : 'Add to List'}</Text>
            </TouchableOpacity>
            {editingKey && (
              <TouchableOpacity style={s.cancelEditBtn} onPress={clearEntry} activeOpacity={0.7}>
                <Text style={s.cancelEditBtnText}>Cancel Edit</Text>
              </TouchableOpacity>
            )}
          </View>

          {/* ── Added Items Table ── */}
          {items.length > 0 && (
            <View style={s.card}>
              <View style={s.tableHeader}>
                <Text style={s.cardTitle}>Purchase Entries ({items.length})</Text>
              </View>

              {/* Column header row */}
              <View style={[s.tableRow, s.tableHeadRow]}>
                <Text style={[s.tableCell, s.col_project, s.headText]}>Project</Text>
                <Text style={[s.tableCell, s.col_product, s.headText]}>Product</Text>
                <Text style={[s.tableCell, s.col_qty, s.headText, { textAlign: 'right' }]}>Qty</Text>
                <Text style={[s.tableCell, s.col_rate, s.headText, { textAlign: 'right' }]}>Rate</Text>
                <Text style={[s.tableCell, s.col_amount, s.headText, { textAlign: 'right' }]}>Amount</Text>
                <Text style={[s.tableCell, s.col_actions, s.headText, { textAlign: 'center' }]}>·</Text>
              </View>

              {items.map((item, idx) => (
                <View key={item.key} style={[s.tableRow, idx % 2 === 1 && s.tableRowAlt]}>
                  <View style={[s.tableCell, s.col_project]}>
                    <Text style={s.cellText} numberOfLines={2}>{item.projectLabel}</Text>
                    {item.supplier ? <Text style={s.cellSub} numberOfLines={1}>{item.supplier}</Text> : null}
                  </View>
                  <View style={[s.tableCell, s.col_product]}>
                    <Text style={s.cellText} numberOfLines={2}>{item.productLabel}</Text>
                    <Text style={s.cellSub}>{item.unit}</Text>
                  </View>
                  <Text style={[s.tableCell, s.col_qty, s.cellText, { textAlign: 'right' }]}>{item.quantity}</Text>
                  <Text style={[s.tableCell, s.col_rate, s.cellText, { textAlign: 'right' }]}>{fmtINR(parseFloat(item.unitRate) || 0)}</Text>
                  <Text style={[s.tableCell, s.col_amount, s.cellAmountText, { textAlign: 'right' }]}>{fmtINR(parseFloat(item.lineAmount) || 0)}</Text>
                  <View style={[s.tableCell, s.col_actions, { flexDirection: 'row', justifyContent: 'center', gap: 6 }]}>
                    <TouchableOpacity onPress={() => handleEdit(item)} hitSlop={{ top: 8, bottom: 8, left: 6, right: 6 }}>
                      <Ionicons name="pencil-outline" size={15} color={Colors.textSecondary} />
                    </TouchableOpacity>
                    <TouchableOpacity onPress={() => handleRemove(item.key)} hitSlop={{ top: 8, bottom: 8, left: 6, right: 6 }}>
                      <Ionicons name="trash-outline" size={15} color={Colors.danger} />
                    </TouchableOpacity>
                  </View>
                </View>
              ))}
            </View>
          )}

          {/* ── Summary ── */}
          {items.length > 0 && (
            <View style={s.summaryCard}>
              <View style={s.summaryRow}>
                <View style={s.summaryBlock}>
                  <Text style={s.summaryLabel}>Total Entries</Text>
                  <Text style={s.summaryNum}>{items.length}</Text>
                </View>
                <View style={s.summaryDivider} />
                <View style={s.summaryBlock}>
                  <Text style={s.summaryLabel}>Purchase Total</Text>
                  <Text style={s.summaryTotal}>{fmtINR(grandTotal)}</Text>
                </View>
              </View>
            </View>
          )}

          {/* ── Actions ── */}
          <TouchableOpacity style={[s.saveBtn, items.length === 0 && s.saveBtnDisabled]} onPress={handleSave} disabled={saving || items.length === 0} activeOpacity={0.85}>
            {saving ? <ActivityIndicator color={Colors.primary} /> : <Text style={s.saveBtnText}>Save Purchase</Text>}
          </TouchableOpacity>
          <TouchableOpacity style={s.cancelBtn} onPress={() => router.back()} disabled={saving} activeOpacity={0.7}>
            <Text style={s.cancelBtnText}>Cancel</Text>
          </TouchableOpacity>

        </RNScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

// ═══════════════════════════════════════════════════════════════════════════════
// EDIT MODE – single-item legacy edit (unchanged)
// ═══════════════════════════════════════════════════════════════════════════════

function EditPurchaseScreen({ id }: { id: string }) {
  const [projects, setProjects] = useState<PickerOption[]>([]);
  const [products, setProducts] = useState<PickerOption[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  const [selProjectId, setSelProjectId] = useState('');
  const [selProductId, setSelProductId] = useState('');
  const [purchaseDate, setPurchaseDate] = useState('');
  const [quantity, setQuantity] = useState('1');
  const [unitAmount, setUnitAmount] = useState('');
  const [supplier, setSupplier] = useState('');
  const [invoiceRef, setInvoiceRef] = useState('');
  const [notes, setNotes] = useState('');

  const lineTotal = (parseFloat(quantity) || 0) * (parseFloat(unitAmount) || 0);

  useEffect(() => {
    Promise.all([
      api.get('/mobile/projects').then((r) => {
        const data = r.data?.data ?? r.data;
        setProjects((data ?? []).map((p: { id: number; name: string; project_number: string }) => ({ label: `${p.project_number} – ${p.name}`, value: String(p.id) })));
      }),
      api.get('/mobile/products').then((r) => {
        const data = r.data?.data ?? r.data;
        setProducts((data ?? []).map((p: { id: number; name: string; unit: string | null }) => ({ label: p.name + (p.unit ? ` (${p.unit})` : ''), value: String(p.id) })));
      }),
      api.get('/mobile/purchases').then((r) => {
        const list = r.data ?? [];
        const p = list.find((x: { id: number }) => String(x.id) === id);
        if (p) {
          setSelProjectId(String(p.project_id ?? ''));
          setSelProductId(String(p.product_id ?? ''));
          setPurchaseDate(p.purchase_date ?? '');
          setQuantity(String(p.quantity ?? 1));
          setUnitAmount(String(p.unit_amount ?? ''));
          setSupplier(p.supplier ?? '');
          setInvoiceRef(p.invoice_reference ?? '');
          setNotes(p.notes ?? '');
        }
      }),
    ]).catch(() => {}).finally(() => setLoading(false));
  }, [id]);

  async function handleSave() {
    if (!selProjectId) { Alert.alert('Required', 'Please select a project.'); return; }
    if (!quantity || parseFloat(quantity) <= 0) { Alert.alert('Required', 'Quantity must be greater than 0.'); return; }
    if (!unitAmount || parseFloat(unitAmount) < 0) { Alert.alert('Required', 'Unit rate is required.'); return; }
    setSaving(true);
    try {
      const body: Record<string, string> = {
        project_id: selProjectId,
        quantity: quantity.trim(),
        unit_amount: unitAmount.trim(),
      };
      if (selProductId) body.product_id = selProductId;
      if (purchaseDate) body.purchase_date = purchaseDate;
      if (supplier.trim()) body.supplier = supplier.trim();
      if (invoiceRef.trim()) body.invoice_reference = invoiceRef.trim();
      if (notes.trim()) body.notes = notes.trim();

      await api.put(`/mobile/purchases/${id}`, body);
      Alert.alert('Saved', 'Purchase updated.', [{ text: 'OK', onPress: () => goBack() }]);
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.message ?? 'Failed to save.');
    } finally { setSaving(false); }
  }

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader title="Edit Purchase" subtitle="Update purchase details" right={<HeaderSaveButton onPress={handleSave} saving={saving} label="Update" />} />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <RNScrollView contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">
          <SelectPicker label="Project" value={selProjectId} options={projects} onChange={setSelProjectId} placeholder="Select project…" searchable required />
          <SelectPicker label="Product / Material" value={selProductId} options={products} onChange={setSelProductId} placeholder="Select product…" searchable />
          <DateField label="Purchase Date" value={purchaseDate} onChange={setPurchaseDate} />
          <View style={s.row2}>
            <View style={{ flex: 1, marginRight: 8 }}>
              <Field label="Quantity" required>
                <Input value={quantity} onChangeText={setQuantity} keyboardType="decimal-pad" placeholder="1" />
              </Field>
            </View>
            <View style={{ flex: 1 }}>
              <Field label="Unit Rate (₹)" required>
                <Input value={unitAmount} onChangeText={setUnitAmount} keyboardType="decimal-pad" placeholder="0.00" />
              </Field>
            </View>
          </View>
          {lineTotal > 0 && (
            <View style={s.totalRow}>
              <Text style={s.totalLabel}>Line Total</Text>
              <Text style={s.totalValue}>{fmtINR(lineTotal)}</Text>
            </View>
          )}
          <Field label="Supplier"><Input value={supplier} onChangeText={setSupplier} placeholder="e.g. ABC Hardware" /></Field>
          <Field label="Invoice / Reference"><Input value={invoiceRef} onChangeText={setInvoiceRef} placeholder="e.g. INV-2024-001" /></Field>
          <Field label="Notes"><Input value={notes} onChangeText={setNotes} placeholder="Any additional notes…" multiline numberOfLines={3} /></Field>
          <TouchableOpacity style={s.saveBtn} onPress={handleSave} disabled={saving} activeOpacity={0.85}>
            {saving ? <ActivityIndicator color={Colors.primary} /> : <Text style={s.saveBtnText}>Update Purchase</Text>}
          </TouchableOpacity>
        </RNScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

// ═══════════════════════════════════════════════════════════════════════════════
// Router entry point
// ═══════════════════════════════════════════════════════════════════════════════

export default function PurchaseFormScreen() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  if (id) return <EditPurchaseScreen id={id} />;
  return <CreatePurchaseScreen />;
}

// ─── Styles ──────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  content: { padding: 14, paddingBottom: 48 },

  card: {
    backgroundColor: Colors.white, borderRadius: 14, padding: 16, marginBottom: 14,
    borderWidth: 1, borderColor: Colors.border,
    shadowColor: Colors.shadow, shadowOffset: { width: 0, height: 2 }, shadowOpacity: 1, shadowRadius: 4, elevation: 2,
  },
  cardTitle: { fontSize: 15, fontWeight: '800', color: Colors.text, marginBottom: 4 },
  cardSub: { fontSize: 12, color: Colors.textSecondary, marginBottom: 14 },
  tableHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 8 },

  field: { marginBottom: 12 },
  label: { fontSize: 13, fontWeight: '600', color: Colors.text, marginBottom: 5 },
  input: {
    borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10,
    backgroundColor: Colors.background, paddingHorizontal: 12, height: 46,
    fontSize: 15, color: Colors.text,
  },
  inputMulti: { height: 88, paddingTop: 12, textAlignVertical: 'top' },

  dateBtn: {
    flexDirection: 'row', alignItems: 'center', borderWidth: 1.5, borderColor: Colors.border,
    borderRadius: 10, backgroundColor: Colors.background, paddingHorizontal: 12, height: 46,
  },
  dateBtnText: { fontSize: 15, color: Colors.text },
  overlay: { flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.35)' },
  pickerCard: { backgroundColor: Colors.white, borderTopLeftRadius: 16, borderTopRightRadius: 16, paddingBottom: 32 },
  pickerHeader: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: 16, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: Colors.border,
  },
  pickerTitle: { fontSize: 15, fontWeight: '700', color: Colors.text },
  pickerCancel: { fontSize: 15, color: Colors.textSecondary },
  pickerDone: { fontSize: 15, fontWeight: '700', color: Colors.accent },

  row2: { flexDirection: 'row' },

  // Auto-calc tag
  autoTag: {
    position: 'absolute', right: 10, top: '50%',
    backgroundColor: Colors.accent + '20', borderRadius: 6, paddingHorizontal: 6, paddingVertical: 2,
    transform: [{ translateY: -11 }],
  },
  autoTagText: { fontSize: 9, fontWeight: '700', color: Colors.accent },
  resetCalc: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 4 },
  resetCalcText: { fontSize: 11, color: Colors.accent, fontWeight: '600' },

  // Add entry button
  addEntryBtn: {
    backgroundColor: Colors.accent, borderRadius: 10, height: 46,
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8, marginTop: 4,
  },
  addEntryBtnText: { fontSize: 15, fontWeight: '800', color: Colors.primary },
  cancelEditBtn: {
    borderRadius: 10, height: 40, alignItems: 'center', justifyContent: 'center', marginTop: 6,
    borderWidth: 1.5, borderColor: Colors.border,
  },
  cancelEditBtnText: { fontSize: 14, color: Colors.textSecondary, fontWeight: '600' },

  // Table
  tableRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 9, borderTopWidth: 1, borderTopColor: Colors.border },
  tableHeadRow: { borderTopWidth: 0, borderBottomWidth: 1, borderBottomColor: Colors.border, paddingVertical: 6 },
  tableRowAlt: { backgroundColor: Colors.background },
  tableCell: { paddingHorizontal: 4 },
  col_project: { flex: 1.8 },
  col_product: { flex: 1.8 },
  col_qty: { flex: 0.8 },
  col_rate: { flex: 1.2 },
  col_amount: { flex: 1.4 },
  col_actions: { flex: 0.8 },
  headText: { fontSize: 10, fontWeight: '700', color: Colors.textSecondary, textTransform: 'uppercase', letterSpacing: 0.3 },
  cellText: { fontSize: 12, color: Colors.text, fontWeight: '500' },
  cellSub: { fontSize: 10, color: Colors.textMuted, marginTop: 1 },
  cellAmountText: { fontSize: 12, fontWeight: '700', color: Colors.text },

  // Summary card
  summaryCard: { backgroundColor: Colors.primary, borderRadius: 14, padding: 18, marginBottom: 14 },
  summaryRow: { flexDirection: 'row', alignItems: 'center' },
  summaryBlock: { flex: 1, alignItems: 'center' },
  summaryDivider: { width: 1, height: 36, backgroundColor: 'rgba(255,255,255,0.2)' },
  summaryLabel: { fontSize: 10, fontWeight: '600', color: 'rgba(255,255,255,0.65)', textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 4 },
  summaryNum: { fontSize: 22, fontWeight: '800', color: Colors.white },
  summaryTotal: { fontSize: 20, fontWeight: '800', color: Colors.accent },

  // Actions
  saveBtn: { backgroundColor: Colors.accent, borderRadius: 12, height: 52, alignItems: 'center', justifyContent: 'center', marginBottom: 10 },
  saveBtnDisabled: { opacity: 0.45 },
  saveBtnText: { fontSize: 16, fontWeight: '800', color: Colors.primary },
  cancelBtn: { borderRadius: 12, height: 46, alignItems: 'center', justifyContent: 'center', borderWidth: 1.5, borderColor: Colors.border, backgroundColor: Colors.white },
  cancelBtnText: { fontSize: 15, fontWeight: '600', color: Colors.textSecondary },

  // Edit-mode specific (legacy)
  totalRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: Colors.accent + '15', borderRadius: 10, padding: 12, marginBottom: 12 },
  totalLabel: { fontSize: 13, fontWeight: '600', color: Colors.textSecondary },
  totalValue: { fontSize: 18, fontWeight: '800', color: Colors.accent },
});
