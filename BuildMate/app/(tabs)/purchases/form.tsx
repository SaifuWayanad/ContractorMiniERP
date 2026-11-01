import React, { useCallback, useEffect, useState } from 'react';
import {
  View, Text, ScrollView, StyleSheet, TextInput,
  TouchableOpacity, ActivityIndicator, Alert, KeyboardAvoidingView, Platform, Modal,
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

function DateField({ label, value, onChange, required }: { label: string; value: string; onChange: (v: string) => void; required?: boolean }) {
  const [show, setShow] = useState(false);
  const date = value ? new Date(value + 'T00:00:00Z') : new Date();
  const display = value ? new Date(value + 'T00:00:00Z').toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' }) : '';
  function onChangeNative(_: any, sel?: Date) { if (Platform.OS === 'android') setShow(false); if (sel) onChange(sel.toISOString().slice(0, 10)); }
  return (
    <Field label={label} required={required}>
      <TouchableOpacity style={s.dateBtn} onPress={() => setShow(true)} activeOpacity={0.7}>
        <Ionicons name="calendar-outline" size={18} color={value ? Colors.text : Colors.textMuted} style={{ marginRight: 8 }} />
        <Text style={[s.dateBtnText, !value && { color: Colors.textMuted }]}>{display || 'Select date'}</Text>
        {value ? <TouchableOpacity onPress={() => onChange('')} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }} style={{ marginLeft: 'auto' }}><Ionicons name="close-circle" size={18} color={Colors.textMuted} /></TouchableOpacity>
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

export default function PurchaseFormScreen() {
  const { id, projectId } = useLocalSearchParams<{ id?: string; projectId?: string }>();
  const isEdit = !!id;

  const [projects, setProjects] = useState<PickerOption[]>([]);
  const [products, setProducts] = useState<PickerOption[]>([]);
  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);

  const [selProjectId, setSelProjectId] = useState(projectId ?? '');
  const [selProductId, setSelProductId] = useState('');
  const [purchaseDate, setPurchaseDate] = useState('');
  const [quantity, setQuantity] = useState('1');
  const [unitAmount, setUnitAmount] = useState('');
  const [supplier, setSupplier] = useState('');
  const [invoiceRef, setInvoiceRef] = useState('');
  const [notes, setNotes] = useState('');

  const lineTotal = (parseFloat(quantity) || 0) * (parseFloat(unitAmount) || 0);

  useFocusEffect(useCallback(() => {
    if (!isEdit) { setSelProjectId(projectId ?? ''); setSelProductId(''); setPurchaseDate(''); setQuantity('1'); setUnitAmount(''); setSupplier(''); setInvoiceRef(''); setNotes(''); }
  }, [isEdit, projectId]));

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
    ]).catch(() => {});

    if (isEdit) {
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
      }).catch(() => {}).finally(() => setLoading(false));
    } else { setLoading(false); }
  }, [id, isEdit]);

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

      if (isEdit) {
        await api.put(`/mobile/purchases/${id}`, body);
        Alert.alert('Saved', 'Purchase updated.', [{ text: 'OK', onPress: () => goBack() }]);
      } else {
        await api.post('/mobile/purchases', body);
        Alert.alert('Created', 'Purchase added.', [{ text: 'OK', onPress: () => {
          if (projectId) router.replace(`/(tabs)/projects/${projectId}`);
          else router.replace('/(tabs)/purchases' as any);
        }}]);
      }
    } catch (err: any) { Alert.alert('Error', err?.response?.data?.message ?? 'Failed to save.'); }
    finally { setSaving(false); }
  }

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader title={isEdit ? 'Edit Purchase' : 'New Purchase'} subtitle={isEdit ? 'Update purchase details' : 'Record a material purchase'} right={<HeaderSaveButton onPress={handleSave} saving={saving} label={isEdit ? 'Update' : 'Save'} />} />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">
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
              <Text style={s.totalValue}>₹{lineTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
            </View>
          )}
          <Field label="Supplier"><Input value={supplier} onChangeText={setSupplier} placeholder="e.g. ABC Hardware" /></Field>
          <Field label="Invoice / Reference"><Input value={invoiceRef} onChangeText={setInvoiceRef} placeholder="e.g. INV-2024-001" /></Field>
          <Field label="Notes"><Input value={notes} onChangeText={setNotes} placeholder="Any additional notes…" multiline numberOfLines={3} /></Field>
          <TouchableOpacity style={s.submitBtn} onPress={handleSave} disabled={saving} activeOpacity={0.85}>
            {saving ? <ActivityIndicator color={Colors.primary} /> : <Text style={s.submitBtnText}>{isEdit ? 'Update Purchase' : 'Add Purchase'}</Text>}
          </TouchableOpacity>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  content: { padding: 16, paddingBottom: 40 },
  field: { marginBottom: 14 },
  label: { fontSize: 13, fontWeight: '600', color: Colors.text, marginBottom: 6 },
  input: { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10, backgroundColor: Colors.white, paddingHorizontal: 12, height: 48, fontSize: 15, color: Colors.text },
  inputMulti: { height: 90, paddingTop: 12, textAlignVertical: 'top' },
  row2: { flexDirection: 'row' },
  totalRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: Colors.accent + '15', borderRadius: 10, padding: 12, marginBottom: 14 },
  totalLabel: { fontSize: 13, fontWeight: '600', color: Colors.textSecondary },
  totalValue: { fontSize: 18, fontWeight: '800', color: Colors.accent },
  dateBtn: { flexDirection: 'row', alignItems: 'center', borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10, backgroundColor: Colors.white, paddingHorizontal: 12, height: 48 },
  dateBtnText: { fontSize: 15, color: Colors.text },
  overlay: { flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.35)' },
  pickerCard: { backgroundColor: Colors.white, borderTopLeftRadius: 16, borderTopRightRadius: 16, paddingBottom: 32 },
  pickerHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 16, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: Colors.border },
  pickerTitle: { fontSize: 15, fontWeight: '700', color: Colors.text },
  pickerCancel: { fontSize: 15, color: Colors.textSecondary },
  pickerDone: { fontSize: 15, fontWeight: '700', color: Colors.accent },
  submitBtn: { backgroundColor: Colors.accent, borderRadius: 12, height: 50, alignItems: 'center', justifyContent: 'center', marginTop: 8 },
  submitBtnText: { fontSize: 16, fontWeight: '800', color: Colors.primary },
});
