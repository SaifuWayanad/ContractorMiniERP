import React, { useCallback, useEffect, useState } from 'react';
import {
  View, Text, StyleSheet, TextInput, TouchableOpacity,
  ActivityIndicator, Alert, KeyboardAvoidingView, Platform, Modal,
  ScrollView as RNScrollView,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, useLocalSearchParams, useFocusEffect } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import DateTimePicker from '@react-native-community/datetimepicker';
import { SelectPicker, PickerOption } from '@/components/ui/SelectPicker';
import { ScreenHeader, HeaderSaveButton } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type PurchaseItem = {
  key: string;
  productId: string;
  quantity: string;
  unitRate: string;
};

type ProjectInfo = {
  id: number;
  name: string;
  project_number: string;
  client: { name: string };
};

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

function ReadonlyField({ label, value }: { label: string; value: string }) {
  return (
    <View style={s.field}>
      <Text style={s.label}>{label}</Text>
      <View style={s.readonlyBox}>
        <Text style={s.readonlyText} numberOfLines={1}>{value}</Text>
        <Ionicons name="lock-closed-outline" size={14} color={Colors.textMuted} />
      </View>
    </View>
  );
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


let keyCounter = 0;
function newItemKey() { return `item_${++keyCounter}`; }
function emptyItem(): PurchaseItem { return { key: newItemKey(), productId: '', quantity: '1', unitRate: '' }; }

export default function ProjectPurchaseFormScreen() {
  const { projectId } = useLocalSearchParams<{ projectId: string }>();
  const [project, setProject] = useState<ProjectInfo | null>(null);
  const [products, setProducts] = useState<PickerOption[]>([]);
  const [loadingProject, setLoadingProject] = useState(true);
  const [saving, setSaving] = useState(false);

  const [purchaseDate, setPurchaseDate] = useState(new Date().toISOString().slice(0, 10));
  const [supplier, setSupplier] = useState('');
  const [invoiceRef, setInvoiceRef] = useState('');
  const [notes, setNotes] = useState('');
  const [items, setItems] = useState<PurchaseItem[]>([emptyItem()]);

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const scrollRef = React.useRef<any>(null);

  useFocusEffect(useCallback(() => {
    setPurchaseDate(new Date().toISOString().slice(0, 10));
    setSupplier('');
    setInvoiceRef('');
    setNotes('');
    setItems([emptyItem()]);
  }, []));

  useEffect(() => {
    Promise.all([
      api.get(`/mobile/projects/${projectId}`).then((r) => {
        setProject(r.data);
      }),
      api.get('/mobile/products').then((r) => {
        const data = r.data?.data ?? r.data;
        setProducts((data ?? []).map((p: { id: number; name: string; unit: string | null }) => ({
          label: p.name + (p.unit ? ` (${p.unit})` : ''),
          value: String(p.id),
        })));
      }),
    ])
      .catch(() => Alert.alert('Error', 'Failed to load project data.'))
      .finally(() => setLoadingProject(false));
  }, [projectId]);

  function updateItem(key: string, field: keyof Omit<PurchaseItem, 'key'>, value: string) {
    setItems((prev) => prev.map((it) => it.key === key ? { ...it, [field]: value } : it));
  }

  function removeItem(key: string) {
    const item = items.find((it) => it.key === key);
    if (item && (item.productId || item.unitRate)) {
      Alert.alert('Remove Item', 'Remove this product row?', [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Remove', style: 'destructive', onPress: () => setItems((prev) => prev.filter((it) => it.key !== key)) },
      ]);
    } else {
      setItems((prev) => prev.filter((it) => it.key !== key));
    }
  }

  function addItem() {
    setItems((prev) => [...prev, emptyItem()]);
    setTimeout(() => scrollRef.current?.scrollToEnd({ animated: true }), 100);
  }

  const purchaseTotal = items.reduce((sum, it) => sum + (parseFloat(it.quantity) || 0) * (parseFloat(it.unitRate) || 0), 0);

  async function handleSave() {
    if (!purchaseDate) { Alert.alert('Required', 'Please select a purchase date.'); return; }

    const validItems = items.filter((it) => parseFloat(it.quantity) > 0 && parseFloat(it.unitRate) >= 0);
    if (validItems.length === 0) { Alert.alert('Required', 'Add at least one product with quantity and unit rate.'); return; }

    const badItem = validItems.find((it) => parseFloat(it.quantity) <= 0);
    if (badItem) { Alert.alert('Required', 'All quantities must be greater than 0.'); return; }

    setSaving(true);
    try {
      await api.post('/mobile/purchases/batch', {
        project_id: projectId,
        purchase_date: purchaseDate,
        supplier: supplier.trim() || undefined,
        invoice_reference: invoiceRef.trim() || undefined,
        notes: notes.trim() || undefined,
        items: validItems.map((it) => ({
          product_id: it.productId ? parseInt(it.productId, 10) : undefined,
          quantity: parseFloat(it.quantity),
          unit_amount: parseFloat(it.unitRate),
        })),
      });

      Alert.alert('Created', 'Purchase added successfully.', [{
        text: 'OK',
        onPress: () => router.replace(`/(tabs)/projects/${projectId}` as any),
      }]);
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.message ?? 'Failed to save purchase.');
    } finally {
      setSaving(false);
    }
  }

  if (loadingProject) {
    return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;
  }

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader
        title="New Purchase"
        subtitle={project ? `${project.project_number} – ${project.name}` : 'Loading…'}
        right={<HeaderSaveButton onPress={handleSave} saving={saving} label="Save" />}
      />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <RNScrollView ref={scrollRef} contentContainerStyle={s.content} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>

          {/* Purchase Details section */}
          <View style={s.sectionCard}>
            <Text style={s.sectionTitle}>Purchase Details</Text>

            <ReadonlyField
              label="Project"
              value={project ? `${project.project_number} – ${project.name}` : '—'}
            />
            <ReadonlyField
              label="Customer"
              value={project?.client?.name ?? '—'}
            />

            <DateField label="Purchase Date" value={purchaseDate} onChange={setPurchaseDate} required />
            <Field label="Invoice / Reference No."><Input value={invoiceRef} onChangeText={setInvoiceRef} placeholder="e.g. INV-2024-001" /></Field>
            <Field label="Supplier"><Input value={supplier} onChangeText={setSupplier} placeholder="e.g. ABC Hardware" /></Field>
            <Field label="Notes"><Input value={notes} onChangeText={setNotes} placeholder="Any additional notes…" multiline numberOfLines={3} /></Field>
          </View>

          {/* Purchase Items section */}
          <View style={s.sectionCard}>
            <Text style={s.sectionTitle}>Purchase Items</Text>
            <Text style={s.sectionSubtitle}>Add one or more products to this purchase</Text>

            {items.map((item, index) => (
              <View key={item.key}>
                {index > 0 && <View style={s.itemDivider} />}
                <View style={s.itemIndexRow}>
                  <View style={s.itemIndexBadge}>
                    <Text style={s.itemIndexText}>{index + 1}</Text>
                  </View>
                  {items.length > 1 && (
                    <TouchableOpacity onPress={() => removeItem(item.key)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }} style={s.removeBtn}>
                      <Ionicons name="trash-outline" size={16} color={Colors.danger} />
                      <Text style={s.removeBtnText}>Remove</Text>
                    </TouchableOpacity>
                  )}
                </View>

                <SelectPicker
                  label="Product / Material"
                  value={item.productId}
                  options={products}
                  onChange={(v) => updateItem(item.key, 'productId', v)}
                  placeholder="Select product…"
                  searchable
                />

                <View style={s.row2}>
                  <View style={{ flex: 1, marginRight: 8 }}>
                    <Field label="Quantity" required>
                      <Input
                        value={item.quantity}
                        onChangeText={(v) => updateItem(item.key, 'quantity', v)}
                        keyboardType="decimal-pad"
                        placeholder="1"
                      />
                    </Field>
                  </View>
                  <View style={{ flex: 1 }}>
                    <Field label="Unit Rate (₹)" required>
                      <Input
                        value={item.unitRate}
                        onChangeText={(v) => updateItem(item.key, 'unitRate', v)}
                        keyboardType="decimal-pad"
                        placeholder="0.00"
                      />
                    </Field>
                  </View>
                </View>

                {(parseFloat(item.quantity) > 0) && (parseFloat(item.unitRate) >= 0) && (
                  <View style={s.lineTotalRow}>
                    <Text style={s.lineTotalLabel}>Line Total</Text>
                    <Text style={s.lineTotalValue}>
                      ₹{((parseFloat(item.quantity) || 0) * (parseFloat(item.unitRate) || 0)).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    </Text>
                  </View>
                )}
              </View>
            ))}

            <TouchableOpacity style={s.addItemBtn} onPress={addItem} activeOpacity={0.8}>
              <Ionicons name="add-circle-outline" size={20} color={Colors.accent} />
              <Text style={s.addItemBtnText}>Add Product</Text>
            </TouchableOpacity>
          </View>

          {/* Purchase Summary */}
          {purchaseTotal > 0 && (
            <View style={s.summaryCard}>
              <View style={s.summaryRow}>
                <View style={s.summaryLeft}>
                  <Text style={s.summaryLabel}>Total Items</Text>
                  <Text style={s.summaryValue}>{items.filter((it) => parseFloat(it.quantity) > 0).length}</Text>
                </View>
                <View style={s.summaryDivider} />
                <View style={s.summaryRight}>
                  <Text style={s.summaryLabel}>Purchase Total</Text>
                  <Text style={s.summaryTotal}>
                    ₹{purchaseTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                  </Text>
                </View>
              </View>
            </View>
          )}

          {/* Actions */}
          <TouchableOpacity style={s.submitBtn} onPress={handleSave} disabled={saving} activeOpacity={0.85}>
            {saving
              ? <ActivityIndicator color={Colors.primary} />
              : <Text style={s.submitBtnText}>Save Purchase</Text>}
          </TouchableOpacity>

          <TouchableOpacity style={s.cancelBtn} onPress={() => router.back()} disabled={saving} activeOpacity={0.7}>
            <Text style={s.cancelBtnText}>Cancel</Text>
          </TouchableOpacity>
        </RNScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  content: { padding: 16, paddingBottom: 48 },

  // Section cards
  sectionCard: {
    backgroundColor: Colors.white,
    borderRadius: 14,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: Colors.border,
    shadowColor: Colors.shadow,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 1,
    shadowRadius: 4,
    elevation: 2,
  },
  sectionTitle: { fontSize: 15, fontWeight: '800', color: Colors.text, marginBottom: 4 },
  sectionSubtitle: { fontSize: 12, color: Colors.textSecondary, marginBottom: 16 },

  // Fields
  field: { marginBottom: 14 },
  label: { fontSize: 13, fontWeight: '600', color: Colors.text, marginBottom: 6 },
  input: {
    borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10,
    backgroundColor: Colors.background, paddingHorizontal: 12, height: 48,
    fontSize: 15, color: Colors.text,
  },
  inputMulti: { height: 90, paddingTop: 12, textAlignVertical: 'top' },

  // Readonly field
  readonlyBox: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10,
    backgroundColor: Colors.background + 'CC', paddingHorizontal: 12, height: 48, opacity: 0.75,
  },
  readonlyText: { fontSize: 15, color: Colors.text, flex: 1, fontWeight: '600' },

  // Date picker
  dateBtn: {
    flexDirection: 'row', alignItems: 'center', borderWidth: 1.5, borderColor: Colors.border,
    borderRadius: 10, backgroundColor: Colors.background, paddingHorizontal: 12, height: 48,
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

  // Item rows
  row2: { flexDirection: 'row' },
  itemDivider: { height: 1, backgroundColor: Colors.border, marginVertical: 16 },
  itemIndexRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 },
  itemIndexBadge: {
    width: 28, height: 28, borderRadius: 14, backgroundColor: Colors.primary,
    alignItems: 'center', justifyContent: 'center',
  },
  itemIndexText: { fontSize: 13, fontWeight: '800', color: Colors.white },
  removeBtn: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  removeBtnText: { fontSize: 13, fontWeight: '600', color: Colors.danger },

  // Line total
  lineTotalRow: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    backgroundColor: Colors.accent + '12', borderRadius: 10, padding: 10, marginBottom: 14,
  },
  lineTotalLabel: { fontSize: 12, fontWeight: '600', color: Colors.textSecondary },
  lineTotalValue: { fontSize: 16, fontWeight: '800', color: Colors.accent },

  // Add item button
  addItemBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8,
    borderWidth: 1.5, borderColor: Colors.accent, borderStyle: 'dashed',
    borderRadius: 10, height: 48, marginTop: 8,
  },
  addItemBtnText: { fontSize: 15, fontWeight: '700', color: Colors.accent },

  // Summary card
  summaryCard: {
    backgroundColor: Colors.primary,
    borderRadius: 14,
    padding: 20,
    marginBottom: 16,
  },
  summaryRow: { flexDirection: 'row', alignItems: 'center' },
  summaryLeft: { flex: 1, alignItems: 'center' },
  summaryRight: { flex: 1.5, alignItems: 'center' },
  summaryDivider: { width: 1, height: 40, backgroundColor: 'rgba(255,255,255,0.2)' },
  summaryLabel: { fontSize: 11, fontWeight: '600', color: 'rgba(255,255,255,0.65)', textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 4 },
  summaryValue: { fontSize: 22, fontWeight: '800', color: Colors.white },
  summaryTotal: { fontSize: 22, fontWeight: '800', color: Colors.accent },

  // Buttons
  submitBtn: {
    backgroundColor: Colors.accent, borderRadius: 12, height: 52,
    alignItems: 'center', justifyContent: 'center', marginBottom: 12,
  },
  submitBtnText: { fontSize: 16, fontWeight: '800', color: Colors.primary },
  cancelBtn: {
    borderRadius: 12, height: 48, alignItems: 'center', justifyContent: 'center',
    borderWidth: 1.5, borderColor: Colors.border, backgroundColor: Colors.white,
  },
  cancelBtnText: { fontSize: 15, fontWeight: '600', color: Colors.textSecondary },

});
