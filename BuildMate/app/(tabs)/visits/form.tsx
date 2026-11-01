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
  function onChangeNative(_: any, sel?: Date) {
    if (Platform.OS === 'android') setShow(false);
    if (sel) onChange(sel.toISOString().slice(0, 10));
  }
  return (
    <Field label={label} required={required}>
      <TouchableOpacity style={s.dateBtn} onPress={() => setShow(true)} activeOpacity={0.7}>
        <Ionicons name="calendar-outline" size={18} color={value ? Colors.text : Colors.textMuted} style={{ marginRight: 8 }} />
        <Text style={[s.dateBtnText, !value && { color: Colors.textMuted }]}>{display || 'Select date'}</Text>
        {value ? (
          <TouchableOpacity onPress={() => onChange('')} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }} style={{ marginLeft: 'auto' }}>
            <Ionicons name="close-circle" size={18} color={Colors.textMuted} />
          </TouchableOpacity>
        ) : <Ionicons name="chevron-down" size={16} color={Colors.textMuted} style={{ marginLeft: 'auto' }} />}
      </TouchableOpacity>
      {Platform.OS === 'android' && show && <DateTimePicker mode="date" value={date} onChange={onChangeNative} />}
      {Platform.OS === 'ios' && (
        <Modal visible={show} transparent animationType="slide" onRequestClose={() => setShow(false)}>
          <View style={s.overlay}>
            <View style={s.pickerCard}>
              <View style={s.pickerHeader}>
                <TouchableOpacity onPress={() => setShow(false)}><Text style={s.pickerCancel}>Cancel</Text></TouchableOpacity>
                <Text style={s.pickerTitle}>{label}</Text>
                <TouchableOpacity onPress={() => setShow(false)}><Text style={s.pickerDone}>Done</Text></TouchableOpacity>
              </View>
              <DateTimePicker mode="date" value={date} onChange={onChangeNative} display="spinner" style={{ height: 200 }} />
            </View>
          </View>
        </Modal>
      )}
    </Field>
  );
}

export default function VisitFormScreen() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  const isEdit = !!id;

  const [clients, setClients] = useState<PickerOption[]>([]);
  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);

  const [clientId, setClientId] = useState('');
  const [meetingDate, setMeetingDate] = useState('');
  const [purpose, setPurpose] = useState('');
  const [location, setLocation] = useState('');
  const [description, setDescription] = useState('');
  const [nextMeetingDate, setNextMeetingDate] = useState('');

  useFocusEffect(useCallback(() => {
    if (!isEdit) { setClientId(''); setMeetingDate(''); setPurpose(''); setLocation(''); setDescription(''); setNextMeetingDate(''); }
  }, [isEdit]));

  const loadClients = useCallback(async () => {
    try {
      const res = await api.get('/mobile/clients');
      const data = res.data?.data ?? res.data;
      setClients((data ?? []).map((c: { id: number; name: string }) => ({ label: c.name, value: String(c.id) })));
    } catch { /* ignore */ }
  }, []);

  const loadVisit = useCallback(async () => {
    if (!id) return;
    try {
      const res = await api.get('/mobile/visits');
      const list = res.data ?? [];
      const visit = list.find((v: { id: number }) => String(v.id) === id);
      if (visit) {
        setClientId(String(visit.client_id ?? ''));
        setMeetingDate(visit.meeting_date ?? '');
        setPurpose(visit.purpose ?? '');
        setLocation(visit.location ?? '');
        setDescription(visit.description ?? '');
        setNextMeetingDate(visit.next_meeting_date ?? '');
      }
    } catch { /* ignore */ } finally { setLoading(false); }
  }, [id]);

  useEffect(() => {
    loadClients();
    if (isEdit) loadVisit(); else setLoading(false);
  }, [loadClients, loadVisit, isEdit]);

  async function handleSave() {
    if (!clientId) { Alert.alert('Required', 'Please select a client.'); return; }
    if (!meetingDate) { Alert.alert('Required', 'Meeting date is required.'); return; }
    setSaving(true);
    try {
      const body: Record<string, string> = { client_id: clientId, meeting_date: meetingDate };
      if (purpose.trim()) body.purpose = purpose.trim();
      if (location.trim()) body.location = location.trim();
      if (description.trim()) body.description = description.trim();
      if (nextMeetingDate) body.next_meeting_date = nextMeetingDate;
      if (isEdit) {
        await api.put(`/mobile/visits/${id}`, body);
        Alert.alert('Saved', 'Visit updated.', [{ text: 'OK', onPress: () => goBack() }]);
      } else {
        await api.post('/mobile/visits', body);
        Alert.alert('Created', 'Visit logged.', [{ text: 'OK', onPress: () => router.replace('/(tabs)/visits' as any) }]);
      }
    } catch (err: any) { Alert.alert('Error', err?.response?.data?.message ?? 'Failed to save.'); }
    finally { setSaving(false); }
  }

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader title={isEdit ? 'Edit Visit' : 'Log Visit'} subtitle={isEdit ? 'Update visit details' : 'Record a customer site visit'} right={<HeaderSaveButton onPress={handleSave} saving={saving} label={isEdit ? 'Update' : 'Save'} />} />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">
          <SelectPicker label="Client" value={clientId} options={clients} onChange={setClientId} placeholder="Select client…" searchable required />
          <DateField label="Visit Date" value={meetingDate} onChange={setMeetingDate} required />
          <Field label="Purpose"><Input value={purpose} onChangeText={setPurpose} placeholder="e.g. Site inspection" /></Field>
          <Field label="Location"><Input value={location} onChangeText={setLocation} placeholder="e.g. Site address" /></Field>
          <Field label="Description / Notes"><Input value={description} onChangeText={setDescription} placeholder="What was discussed…" multiline numberOfLines={4} /></Field>
          <DateField label="Next Meeting Date" value={nextMeetingDate} onChange={setNextMeetingDate} />
          <TouchableOpacity style={s.submitBtn} onPress={handleSave} disabled={saving} activeOpacity={0.85}>
            {saving ? <ActivityIndicator color={Colors.primary} /> : <Text style={s.submitBtnText}>{isEdit ? 'Update Visit' : 'Log Visit'}</Text>}
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
  inputMulti: { height: 100, paddingTop: 12, textAlignVertical: 'top' },
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
