import React, { useCallback, useEffect, useState } from 'react';
import {
  View, Text, ScrollView, StyleSheet, TextInput,
  TouchableOpacity, ActivityIndicator, Alert, KeyboardAvoidingView, Platform, Modal,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, useLocalSearchParams } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import DateTimePicker from '@react-native-community/datetimepicker';
import { SelectPicker, PickerOption } from '@/components/ui/SelectPicker';
import { ScreenHeader, HeaderSaveButton } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import { useAuth } from '@/hooks/useAuth';
import api from '@/services/api';

const STATUS_OPTIONS: PickerOption[] = [
  { label: 'Pending', value: 'pending' },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'On Hold', value: 'on_hold' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
];

const PRIORITY_OPTIONS: PickerOption[] = [
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
  { label: 'Urgent', value: 'urgent' },
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

      {Platform.OS === 'android' && show && (
        <DateTimePicker mode="date" value={date} onChange={onChangeNative} />
      )}

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

export default function WorkFormScreen() {
  const { id, projectId } = useLocalSearchParams<{ id?: string; projectId?: string }>();
  const { user } = useAuth();
  const isEdit = !!id;
  const isAdmin = user?.is_admin ?? false;

  const [projects, setProjects] = useState<PickerOption[]>([]);
  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);

  const [selectedProjectId, setSelectedProjectId] = useState(projectId ?? '');
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [status, setStatus] = useState('pending');
  const [priority, setPriority] = useState('medium');
  const [dueDate, setDueDate] = useState('');

  const loadProjects = useCallback(async () => {
    try {
      const res = await api.get('/mobile/projects');
      const data = res.data.data ?? res.data;
      setProjects(data.map((p: { id: number; name: string; project_number: string }) => ({
        label: `${p.project_number} – ${p.name}`,
        value: String(p.id),
      })));
    } catch { /* ignore */ }
  }, []);

  const loadWork = useCallback(async () => {
    if (!id) return;
    try {
      const res = await api.get('/mobile/works');
      const data = res.data.data ?? res.data;
      const work = data.find((w: { id: number }) => String(w.id) === id);
      if (work) {
        setSelectedProjectId(String(work.project_id ?? ''));
        setTitle(work.title ?? '');
        setStatus(work.status ?? 'pending');
        setPriority(work.priority ?? 'medium');
        setDueDate(work.due_date ?? '');
      }
    } catch { /* ignore */ } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    loadProjects();
    loadWork();
    if (!isEdit) setLoading(false);
  }, [loadProjects, loadWork, isEdit]);

  async function handleSave() {
    if (!title.trim()) { Alert.alert('Required', 'Work title is required.'); return; }
    if (!selectedProjectId) { Alert.alert('Required', 'Please select a project.'); return; }

    setSaving(true);
    try {
      const selectedProject = projects.find((p) => p.value === selectedProjectId);
      const body: Record<string, string> = {
        project_id: selectedProjectId,
        title: title.trim(),
        status,
        priority,
      };
      if (description.trim()) body.description = description.trim();
      if (dueDate.trim()) body.due_date = dueDate.trim();

      if (isEdit) {
        await api.patch(`/mobile/works/${id}`, body);
        Alert.alert('Saved', 'Work updated.', [{ text: 'OK', onPress: () => router.back() }]);
      } else {
        await api.post('/mobile/works', body);
        Alert.alert('Created', 'Work added.', [{ text: 'OK', onPress: () => router.navigate('/(tabs)/works') }]);
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
        title={isEdit ? 'Edit Work' : 'New Work'}
        subtitle={isEdit ? 'Update work item details' : 'Fill in the details below'}
        right={<HeaderSaveButton onPress={handleSave} saving={saving} label={isEdit ? 'Update' : 'Save'} />}
      />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
          <ScrollView contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">

            <SelectPicker
              label="Project"
              value={selectedProjectId}
              options={projects}
              onChange={setSelectedProjectId}
              placeholder="Select project…"
              searchable
              required
            />

            <Field label="Work Title" required>
              <Input value={title} onChangeText={setTitle} placeholder="e.g. Tile Flooring – Bedroom 1" />
            </Field>

            <Field label="Description">
              <Input value={description} onChangeText={setDescription} placeholder="Details about this work…" multiline numberOfLines={3} />
            </Field>

            <View style={s.row2}>
              <View style={{ flex: 1, marginRight: 8 }}>
                <SelectPicker label="Priority" value={priority} options={PRIORITY_OPTIONS} onChange={setPriority} />
              </View>
              <View style={{ flex: 1 }}>
                <SelectPicker label="Status" value={status} options={STATUS_OPTIONS} onChange={setStatus} />
              </View>
            </View>

            <DatePickerField label="Due Date" value={dueDate} onChange={setDueDate} />

            <TouchableOpacity style={s.submitBtn} onPress={handleSave} disabled={saving} activeOpacity={0.85}>
              {saving ? <ActivityIndicator color={Colors.primary} /> : <Text style={s.submitBtnText}>{isEdit ? 'Update Work' : 'Add Work'}</Text>}
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
