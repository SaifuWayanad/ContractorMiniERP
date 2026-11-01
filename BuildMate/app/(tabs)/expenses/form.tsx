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

const PAYMENT_METHODS: PickerOption[] = [
  { label: 'Cash', value: 'cash' },
  { label: 'Bank Transfer', value: 'bank_transfer' },
  { label: 'UPI', value: 'upi' },
  { label: 'Cheque', value: 'cheque' },
  { label: 'Credit Card', value: 'credit_card' },
  { label: 'Other', value: 'other' },
];

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

export default function ExpenseFormScreen() {
  const { id, projectId } = useLocalSearchParams<{ id?: string; projectId?: string }>();
  const isEdit = !!id;

  const [projects, setProjects] = useState<PickerOption[]>([]);
  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);

  const [selProjectId, setSelProjectId] = useState(projectId ?? '');
  const [heading, setHeading] = useState('');
  const [category, setCategory] = useState('');
  const [expenseDate, setExpenseDate] = useState('');
  const [amount, setAmount] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('');
  const [referenceNumber, setReferenceNumber] = useState('');
  const [notes, setNotes] = useState('');

  useFocusEffect(useCallback(() => {
    if (!isEdit) { setSelProjectId(projectId ?? ''); setHeading(''); setCategory(''); setExpenseDate(''); setAmount(''); setPaymentMethod(''); setReferenceNumber(''); setNotes(''); }
  }, [isEdit, projectId]));

  useEffect(() => {
    api.get('/mobile/projects').then((r) => {
      const data = r.data?.data ?? r.data;
      setProjects((data ?? []).map((p: { id: number; name: string; project_number: string }) => ({ label: `${p.project_number} – ${p.name}`, value: String(p.id) })));
    }).catch(() => {});

    if (isEdit) {
      api.get('/mobile/expenses').then((r) => {
        const list = r.data ?? [];
        const e = list.find((x: { id: number }) => String(x.id) === id);
        if (e) {
          setSelProjectId(String(e.project_id ?? ''));
          setHeading(e.heading ?? '');
          setCategory(e.category ?? '');
          setExpenseDate(e.expense_date ?? '');
          setAmount(String(e.amount ?? ''));
          setPaymentMethod(e.payment_method ?? '');
          setReferenceNumber(e.reference_number ?? '');
          setNotes(e.notes ?? '');
        }
      }).catch(() => {}).finally(() => setLoading(false));
    } else { setLoading(false); }
  }, [id, isEdit]);

  async function handleSave() {
    if (!selProjectId) { Alert.alert('Required', 'Please select a project.'); return; }
    if (!heading.trim()) { Alert.alert('Required', 'Heading is required.'); return; }
    if (!amount || parseFloat(amount) < 0) { Alert.alert('Required', 'Amount is required.'); return; }
    setSaving(true);
    try {
      const body: Record<string, string> = { project_id: selProjectId, heading: heading.trim(), amount: amount.trim() };
      if (category.trim()) body.category = category.trim();
      if (expenseDate) body.expense_date = expenseDate;
      if (paymentMethod) body.payment_method = paymentMethod;
      if (referenceNumber.trim()) body.reference_number = referenceNumber.trim();
      if (notes.trim()) body.notes = notes.trim();

      if (isEdit) {
        await api.put(`/mobile/expenses/${id}`, body);
        Alert.alert('Saved', 'Expense updated.', [{ text: 'OK', onPress: () => goBack() }]);
      } else {
        await api.post('/mobile/expenses', body);
        Alert.alert('Created', 'Expense added.', [{ text: 'OK', onPress: () => {
          if (projectId) router.replace(`/(tabs)/projects/${projectId}`);
          else router.replace('/(tabs)/expenses' as any);
        }}]);
      }
    } catch (err: any) { Alert.alert('Error', err?.response?.data?.message ?? 'Failed to save.'); }
    finally { setSaving(false); }
  }

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader title={isEdit ? 'Edit Expense' : 'New Expense'} subtitle={isEdit ? 'Update expense details' : 'Log a project expense'} right={<HeaderSaveButton onPress={handleSave} saving={saving} label={isEdit ? 'Update' : 'Save'} />} />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">
          <SelectPicker label="Project" value={selProjectId} options={projects} onChange={setSelProjectId} placeholder="Select project…" searchable required />
          <Field label="Heading / Description" required><Input value={heading} onChangeText={setHeading} placeholder="e.g. Electrical wiring" /></Field>
          <Field label="Category"><Input value={category} onChangeText={setCategory} placeholder="e.g. Labour, Material, Transport" /></Field>
          <View style={s.row2}>
            <View style={{ flex: 1, marginRight: 8 }}>
              <Field label="Amount (₹)" required><Input value={amount} onChangeText={setAmount} keyboardType="decimal-pad" placeholder="0.00" /></Field>
            </View>
            <View style={{ flex: 1 }}>
              <SelectPicker label="Payment Method" value={paymentMethod} options={PAYMENT_METHODS} onChange={setPaymentMethod} placeholder="Select…" />
            </View>
          </View>
          <DateField label="Expense Date" value={expenseDate} onChange={setExpenseDate} />
          <Field label="Reference Number"><Input value={referenceNumber} onChangeText={setReferenceNumber} placeholder="e.g. RCP-001" /></Field>
          <Field label="Notes"><Input value={notes} onChangeText={setNotes} placeholder="Any additional notes…" multiline numberOfLines={3} /></Field>
          <TouchableOpacity style={s.submitBtn} onPress={handleSave} disabled={saving} activeOpacity={0.85}>
            {saving ? <ActivityIndicator color={Colors.primary} /> : <Text style={s.submitBtnText}>{isEdit ? 'Update Expense' : 'Add Expense'}</Text>}
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
