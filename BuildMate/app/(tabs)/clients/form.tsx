import React, { useCallback, useEffect, useState } from 'react';
import {
  View, Text, ScrollView, StyleSheet, TextInput,
  TouchableOpacity, ActivityIndicator, Alert, KeyboardAvoidingView, Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, useLocalSearchParams, useFocusEffect } from 'expo-router';
import { SelectPicker, PickerOption } from '@/components/ui/SelectPicker';
import { ScreenHeader, HeaderSaveButton } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import { useAuth } from '@/hooks/useAuth';
import api from '@/services/api';

const STATUS_OPTIONS: PickerOption[] = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
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

export default function ClientFormScreen() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  const { user } = useAuth();
  const isEdit = !!id;
  const isAdmin = user?.is_admin ?? false;

  const [contractors, setContractors] = useState<PickerOption[]>([]);
  const [contractorId, setContractorId] = useState('');
  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [city, setCity] = useState('');
  const [state, setState] = useState('');
  const [status, setStatus] = useState('active');
  const [notes, setNotes] = useState('');

  useFocusEffect(
    useCallback(() => {
      if (!isEdit) {
        setName(''); setEmail(''); setPhone(''); setAddress('');
        setCity(''); setState(''); setStatus('active'); setNotes('');
        setContractorId('');
      }
    }, [isEdit])
  );

  const loadClient = useCallback(async () => {
    if (!id) return;
    try {
      const res = await api.get(`/mobile/clients/${id}`);
      const c = res.data;
      setName(c.name ?? '');
      setEmail(c.email ?? '');
      setPhone(c.phone ?? '');
      setAddress(c.address ?? '');
      setCity(c.city ?? '');
      setState(c.state ?? '');
      setStatus(c.status ?? 'active');
      setNotes(c.notes ?? '');
    } catch { /* ignore */ } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    loadClient();
    if (!isEdit) setLoading(false);
    if (isAdmin) {
      api.get('/mobile/contractors').then((res) => {
        setContractors((res.data ?? []).map((c: { id: number; name: string }) => ({ label: c.name, value: String(c.id) })));
      }).catch(() => {});
    }
  }, [loadClient, isEdit, isAdmin]);

  async function handleSave() {
    if (!name.trim()) { Alert.alert('Required', 'Client name is required.'); return; }
    if (isAdmin && !isEdit && !contractorId) { Alert.alert('Required', 'Please select a contractor.'); return; }

    setSaving(true);
    try {
      const body: Record<string, string> = { name: name.trim(), status };
      if (isAdmin && contractorId) body.contractor_id = contractorId;
      if (email.trim()) body.email = email.trim();
      if (phone.trim()) body.phone = phone.trim();
      if (address.trim()) body.address = address.trim();
      if (city.trim()) body.city = city.trim();
      if (state.trim()) body.state = state.trim();
      if (notes.trim()) body.notes = notes.trim();

      if (isEdit) {
        await api.put(`/mobile/clients/${id}`, body);
        Alert.alert('Saved', 'Client updated.', [{ text: 'OK', onPress: () => router.back() }]);
      } else {
        await api.post('/mobile/clients', body);
        Alert.alert('Created', 'Client added.', [{ text: 'OK', onPress: () => router.navigate('/(tabs)/clients') }]);
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
        title={isEdit ? 'Edit Client' : 'New Client'}
        subtitle={isEdit ? 'Update client information' : 'Fill in the details below'}
        right={<HeaderSaveButton onPress={handleSave} saving={saving} label={isEdit ? 'Update' : 'Save'} />}
      />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
          <ScrollView contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">

            {isAdmin && !isEdit && (
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

            <Field label="Client Name" required>
              <Input value={name} onChangeText={setName} placeholder="e.g. Rajesh Kumar" />
            </Field>

            <View style={s.row2}>
              <View style={{ flex: 1, marginRight: 8 }}>
                <Field label="Phone">
                  <Input value={phone} onChangeText={setPhone} placeholder="+91 9876543210" keyboardType="phone-pad" />
                </Field>
              </View>
              <View style={{ flex: 1 }}>
                <Field label="Email">
                  <Input value={email} onChangeText={setEmail} placeholder="email@example.com" keyboardType="email-address" autoCapitalize="none" />
                </Field>
              </View>
            </View>

            <Field label="Address">
              <Input value={address} onChangeText={setAddress} placeholder="Street address" />
            </Field>

            <View style={s.row2}>
              <View style={{ flex: 1, marginRight: 8 }}>
                <Field label="City">
                  <Input value={city} onChangeText={setCity} placeholder="City" />
                </Field>
              </View>
              <View style={{ flex: 1 }}>
                <Field label="State">
                  <Input value={state} onChangeText={setState} placeholder="State" />
                </Field>
              </View>
            </View>

            {isEdit && (
              <SelectPicker
                label="Status"
                value={status}
                options={STATUS_OPTIONS}
                onChange={setStatus}
              />
            )}

            <Field label="Notes">
              <Input value={notes} onChangeText={setNotes} placeholder="Any notes…" multiline numberOfLines={3} />
            </Field>

            <TouchableOpacity style={s.submitBtn} onPress={handleSave} disabled={saving} activeOpacity={0.85}>
              {saving ? <ActivityIndicator color={Colors.primary} /> : <Text style={s.submitBtnText}>{isEdit ? 'Update Client' : 'Add Client'}</Text>}
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
  submitBtn: {
    backgroundColor: Colors.accent, borderRadius: 12, height: 50,
    alignItems: 'center', justifyContent: 'center', marginTop: 8,
  },
  submitBtnText: { fontSize: 16, fontWeight: '800', color: Colors.primary },
});
