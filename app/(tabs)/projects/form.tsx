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
import { useAuth } from '@/hooks/useAuth';
import api from '@/services/api';

const STATUS_OPTIONS: PickerOption[] = [
  { label: 'Planning', value: 'planning' },
  { label: 'Active', value: 'active' },
  { label: 'On Hold', value: 'on_hold' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
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
  return (
    <TextInput
      style={[s.input, props.multiline && s.inputMulti]}
      placeholderTextColor={Colors.textMuted}
      {...props}
    />
  );
}

function DatePickerField({ label, value, onChange, required }: {
  label: string; value: string; onChange: (date: string) => void; required?: boolean;
}) {
  const [show, setShow] = useState(false);
  const date = value ? new Date(value) : new Date();
  const display = value
    ? new Date(value).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })
    : '';

  function onChangeNative(_: any, selected?: Date) {
    if (Platform.OS === 'android') setShow(false);
    if (selected) onChange(selected.toISOString().slice(0, 10));
  }

  return (
    <View style={s.field}>
      <Text style={s.label}>{label}{required && <Text style={{ color: Colors.danger }}> *</Text>}</Text>
      <TouchableOpacity style={s.dateBtn} onPress={() => setShow(true)} activeOpacity={0.7}>
        <Ionicons name="calendar-outline" size={18} color={value ? Colors.text : Colors.textMuted} style={{ marginRight: 8 }} />
        <Text style={[s.dateBtnText, !value && { color: Colors.textMuted }]}>
          {display || 'Select date'}
        </Text>
        {value ? (
          <TouchableOpacity onPress={() => onChange('')} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }} style={{ marginLeft: 'auto' }}>
            <Ionicons name="close-circle" size={18} color={Colors.textMuted} />
          </TouchableOpacity>
        ) : (
          <Ionicons name="chevron-down" size={16} color={Colors.textMuted} style={{ marginLeft: 'auto' }} />
        )}
      </TouchableOpacity>

      {/* Android: native dialog */}
      {Platform.OS === 'android' && show && (
        <DateTimePicker mode="date" value={date} onChange={onChangeNative} />
      )}

      {/* iOS: modal with inline spinner */}
      {Platform.OS === 'ios' && (
        <Modal visible={show} transparent animationType="slide">
          <View style={s.iosPickerOverlay}>
            <View style={s.iosPickerCard}>
              <View style={s.iosPickerHeader}>
                <TouchableOpacity onPress={() => setShow(false)}>
                  <Text style={s.iosPickerCancel}>Cancel</Text>
                </TouchableOpacity>
                <Text style={s.iosPickerTitle}>{label}</Text>
                <TouchableOpacity onPress={() => setShow(false)}>
                  <Text style={s.iosPickerDone}>Done</Text>
                </TouchableOpacity>
              </View>
              <DateTimePicker mode="date" value={date} onChange={onChangeNative} display="spinner" style={{ height: 200 }} />
            </View>
          </View>
        </Modal>
      )}
    </View>
  );
}

function toLocalISO(date: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function DateTimePickerField({ label, value, onChange }: {
  label: string; value: string; onChange: (dt: string) => void;
}) {
  const [iosShow, setIosShow] = useState(false);
  const [iosPending, setIosPending] = useState<Date>(new Date());
  const [androidStep, setAndroidStep] = useState<'date' | 'time' | null>(null);
  const [androidDate, setAndroidDate] = useState<Date>(new Date());

  const dateObj = value ? new Date(value) : new Date();
  const display = value
    ? new Date(value).toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true })
    : '';

  function handlePress() {
    if (Platform.OS === 'android') {
      setAndroidDate(value ? new Date(value) : new Date());
      setAndroidStep('date');
    } else {
      setIosPending(value ? new Date(value) : new Date());
      setIosShow(true);
    }
  }

  function onAndroidDate(_: any, sel?: Date) {
    if (!sel) { setAndroidStep(null); return; }
    setAndroidDate(sel);
    setAndroidStep('time');
  }

  function onAndroidTime(_: any, sel?: Date) {
    setAndroidStep(null);
    if (!sel) return;
    const combined = new Date(androidDate);
    combined.setHours(sel.getHours(), sel.getMinutes(), 0, 0);
    onChange(toLocalISO(combined));
  }

  return (
    <View style={s.field}>
      <Text style={s.label}>{label}</Text>
      <TouchableOpacity style={s.dateBtn} onPress={handlePress} activeOpacity={0.7}>
        <Ionicons name="time-outline" size={18} color={value ? Colors.text : Colors.textMuted} style={{ marginRight: 8 }} />
        <Text style={[s.dateBtnText, !value && { color: Colors.textMuted }]}>
          {display || 'Select date & time'}
        </Text>
        {value ? (
          <TouchableOpacity onPress={() => onChange('')} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }} style={{ marginLeft: 'auto' }}>
            <Ionicons name="close-circle" size={18} color={Colors.textMuted} />
          </TouchableOpacity>
        ) : (
          <Ionicons name="chevron-down" size={16} color={Colors.textMuted} style={{ marginLeft: 'auto' }} />
        )}
      </TouchableOpacity>

      {Platform.OS === 'android' && androidStep === 'date' && (
        <DateTimePicker mode="date" value={androidDate} onChange={onAndroidDate} />
      )}
      {Platform.OS === 'android' && androidStep === 'time' && (
        <DateTimePicker mode="time" value={androidDate} onChange={onAndroidTime} is24Hour={false} />
      )}

      {Platform.OS === 'ios' && (
        <Modal visible={iosShow} transparent animationType="slide">
          <View style={s.iosPickerOverlay}>
            <View style={s.iosPickerCard}>
              <View style={s.iosPickerHeader}>
                <TouchableOpacity onPress={() => setIosShow(false)}>
                  <Text style={s.iosPickerCancel}>Cancel</Text>
                </TouchableOpacity>
                <Text style={s.iosPickerTitle}>{label}</Text>
                <TouchableOpacity onPress={() => { onChange(toLocalISO(iosPending)); setIosShow(false); }}>
                  <Text style={s.iosPickerDone}>Done</Text>
                </TouchableOpacity>
              </View>
              <DateTimePicker
                mode="datetime"
                value={iosPending}
                onChange={(_, d) => { if (d) setIosPending(d); }}
                display="spinner"
                style={{ height: 200 }}
              />
            </View>
          </View>
        </Modal>
      )}
    </View>
  );
}

export default function ProjectFormScreen() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  const { user } = useAuth();
  const isEdit = !!id;
  const isAdmin = user?.is_admin ?? false;

  const [clients, setClients] = useState<PickerOption[]>([]);
  const [contractors, setContractors] = useState<PickerOption[]>([]);
  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);

  const [contractorId, setContractorId] = useState('');
  const [clientId, setClientId] = useState('');
  const [name, setName] = useState('');
  const [projectNumber, setProjectNumber] = useState('');
  const [typeOfWork, setTypeOfWork] = useState('');
  const [status, setStatus] = useState('planning');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [budget, setBudget] = useState('');
  const [notes, setNotes] = useState('');
  const [meetingAt, setMeetingAt] = useState('');
  const [meetingNotes, setMeetingNotes] = useState('');

  useFocusEffect(
    useCallback(() => {
      if (!isEdit) {
        setContractorId(''); setClientId(''); setName(''); setProjectNumber('');
        setTypeOfWork(''); setStatus('planning'); setStartDate(''); setEndDate('');
        setBudget(''); setNotes(''); setMeetingAt(''); setMeetingNotes('');
      }
    }, [isEdit])
  );

  const loadClients = useCallback(async () => {
    try {
      const res = await api.get('/mobile/clients');
      const data = res.data.data ?? res.data;
      setClients(data.map((c: { id: number; name: string; company: string | null }) => ({
        label: c.company ? `${c.name} (${c.company})` : c.name,
        value: String(c.id),
      })));
    } catch { /* ignore */ }
  }, []);

  const loadContractors = useCallback(async () => {
    if (!isAdmin) return;
    try {
      const res = await api.get('/mobile/contractors');
      setContractors((res.data ?? []).map((c: { id: number; name: string }) => ({ label: c.name, value: String(c.id) })));
    } catch { /* ignore */ }
  }, [isAdmin]);

  const loadProject = useCallback(async () => {
    if (!id) return;
    try {
      const res = await api.get(`/mobile/projects/${id}`);
      const p = res.data;
      setClientId(String(p.client_id));
      setName(p.name ?? '');
      setProjectNumber(p.project_number ?? '');
      setTypeOfWork(p.type_of_work ?? '');
      setStatus(p.status ?? 'planning');
      setStartDate(p.start_date ?? '');
      setEndDate(p.expected_end_date ?? '');
      setBudget(p.budget ? String(p.budget) : '');
      setNotes(p.notes ?? '');
      if (p.next_meeting_at) {
        // Strip Z/timezone so JavaScript parses as local time, not UTC
        setMeetingAt(String(p.next_meeting_at).replace('Z', '').replace(' ', 'T').slice(0, 16));
      }
      setMeetingNotes(p.next_meeting_notes ?? '');
    } catch { /* ignore */ } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    loadClients();
    loadContractors();
    loadProject();
    if (!isEdit) setLoading(false);
  }, [loadClients, loadContractors, loadProject, isEdit]);

  async function handleSave() {
    if (!name.trim()) { Alert.alert('Required', 'Project name is required.'); return; }
    if (!clientId) { Alert.alert('Required', 'Please select a client.'); return; }

    if (isAdmin && !contractorId) { Alert.alert('Required', 'Please select a contractor.'); return; }

    setSaving(true);
    try {
      const body: Record<string, string> = {
        client_id: clientId,
        name: name.trim(),
        status,
      };
      if (isAdmin && contractorId) body.contractor_id = contractorId;
      if (projectNumber.trim()) body.project_number = projectNumber.trim();
      if (typeOfWork.trim()) body.type_of_work = typeOfWork.trim();
      if (startDate.trim()) body.start_date = startDate.trim();
      if (endDate.trim()) body.expected_end_date = endDate.trim();
      if (budget.trim()) body.budget = budget.trim();
      if (notes.trim()) body.notes = notes.trim();
      if (meetingAt.trim()) body.next_meeting_at = meetingAt.trim();
      if (meetingNotes.trim()) body.next_meeting_notes = meetingNotes.trim();

      if (isEdit) {
        await api.put(`/mobile/projects/${id}`, body);
        Alert.alert('Saved', 'Project updated.', [{ text: 'OK', onPress: () => router.back() }]);
      } else {
        const res = await api.post('/mobile/projects', body);
        Alert.alert('Created', `"${res.data.name}" has been created.`, [{ text: 'OK', onPress: () => router.navigate('/(tabs)/projects') }]);
      }
    } catch (err: any) {
      const msg = err?.response?.data?.message ?? 'Failed to save.';
      Alert.alert('Error', msg);
    } finally {
      setSaving(false);
    }
  }

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader
        title={isEdit ? 'Edit Project' : 'New Project'}
        subtitle={isEdit ? 'Update project details' : 'Fill in the details below'}
        right={<HeaderSaveButton onPress={handleSave} saving={saving} label={isEdit ? 'Update' : 'Save'} />}
      />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
          <ScrollView contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">

            {isAdmin && (
              <SelectPicker
                label="Contractor"
                value={contractorId}
                options={contractors}
                onChange={setContractorId}
                placeholder="Select contractor…"
                searchable
                required
              />
            )}

            <SelectPicker
              label="Client"
              value={clientId}
              options={clients}
              onChange={setClientId}
              placeholder="Select client…"
              searchable
              required
            />

            <Field label="Project Name" required>
              <Input value={name} onChangeText={setName} placeholder="e.g. Living Room Renovation" />
            </Field>

            <Field label="Project Number">
              <Input value={projectNumber} onChangeText={setProjectNumber} placeholder="Auto-generated if empty" autoCapitalize="characters" />
            </Field>

            <Field label="Type of Work">
              <Input value={typeOfWork} onChangeText={setTypeOfWork} placeholder="e.g. Interior, Civil" />
            </Field>

            <SelectPicker
              label="Status"
              value={status}
              options={STATUS_OPTIONS}
              onChange={setStatus}
            />

            <View style={s.row2}>
              <View style={{ flex: 1, marginRight: 8 }}>
                <DatePickerField label="Start Date" value={startDate} onChange={setStartDate} />
              </View>
              <View style={{ flex: 1 }}>
                <DatePickerField label="End Date" value={endDate} onChange={setEndDate} />
              </View>
            </View>

            <Field label="Budget (₹)">
              <Input value={budget} onChangeText={setBudget} placeholder="0" keyboardType="numeric" />
            </Field>

            <Field label="Notes">
              <Input value={notes} onChangeText={setNotes} placeholder="Any notes…" multiline numberOfLines={3} />
            </Field>

            <View style={s.sectionTitle}>
              <Ionicons name="calendar-outline" size={16} color={Colors.accent} />
              <Text style={s.sectionTitleText}>Next Meeting / Site Visit</Text>
            </View>

            <DateTimePickerField label="Date & Time" value={meetingAt} onChange={setMeetingAt} />

            <Field label="Meeting Notes">
              <Input value={meetingNotes} onChangeText={setMeetingNotes} placeholder="e.g. Site inspection with client" maxLength={250} />
            </Field>

            <TouchableOpacity style={s.submitBtn} onPress={handleSave} disabled={saving} activeOpacity={0.85}>
              {saving ? <ActivityIndicator color={Colors.primary} /> : <Text style={s.submitBtnText}>{isEdit ? 'Update Project' : 'Create Project'}</Text>}
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
  input: {
    borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10,
    backgroundColor: Colors.white, paddingHorizontal: 12, height: 48,
    fontSize: 15, color: Colors.text,
  },
  inputMulti: { height: 90, paddingTop: 12, textAlignVertical: 'top' },
  row2: { flexDirection: 'row' },
  sectionTitle: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    marginBottom: 12, marginTop: 4, paddingBottom: 8,
    borderBottomWidth: 1, borderBottomColor: Colors.border,
  },
  sectionTitleText: { fontSize: 14, fontWeight: '700', color: Colors.accent },
  dateBtn: {
    flexDirection: 'row', alignItems: 'center',
    borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10,
    backgroundColor: Colors.white, paddingHorizontal: 12, height: 48,
  },
  dateBtnText: { fontSize: 15, color: Colors.text },
  iosPickerOverlay: {
    flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.35)',
  },
  iosPickerCard: {
    backgroundColor: Colors.white, borderTopLeftRadius: 16, borderTopRightRadius: 16,
    paddingBottom: 32,
  },
  iosPickerHeader: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: 16, paddingVertical: 14,
    borderBottomWidth: 1, borderBottomColor: Colors.border,
  },
  iosPickerTitle: { fontSize: 15, fontWeight: '700', color: Colors.text },
  iosPickerCancel: { fontSize: 15, color: Colors.textSecondary },
  iosPickerDone: { fontSize: 15, fontWeight: '700', color: Colors.accent },
  submitBtn: {
    backgroundColor: Colors.accent, borderRadius: 12, height: 50,
    alignItems: 'center', justifyContent: 'center', marginTop: 8,
  },
  submitBtnText: { fontSize: 16, fontWeight: '800', color: Colors.primary },
});
