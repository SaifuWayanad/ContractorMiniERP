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
import api from '@/services/api';
import { goBack } from '@/services/navigation';

const STATUS_OPTIONS: PickerOption[] = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

type RoleData = { id: number; name: string; type: string };

function Field({ label, required, children }: { label: string; required?: boolean; children: React.ReactNode }) {
  return (
    <View style={s.field}>
      <Text style={s.label}>{label}{required && <Text style={{ color: Colors.danger }}> *</Text>}</Text>
      {children}
    </View>
  );
}

function Input(props: React.ComponentProps<typeof TextInput> & { secureTextEntry?: boolean }) {
  return <TextInput style={s.input} placeholderTextColor={Colors.textMuted} {...props} />;
}

export default function UserFormScreen() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  const isEdit = !!id;

  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);

  const [name, setName] = useState('');
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [roleId, setRoleId] = useState('');
  const [contractorId, setContractorId] = useState('');
  const [status, setStatus] = useState('active');

  const [rolesData, setRolesData] = useState<RoleData[]>([]);
  const [contractors, setContractors] = useState<PickerOption[]>([]);

  const roleOptions: PickerOption[] = rolesData.map((r) => ({ label: r.name, value: String(r.id) }));
  const selectedRole = rolesData.find((r) => String(r.id) === roleId);
  const isContractorRole = selectedRole?.type === 'contractor';

  useFocusEffect(
    useCallback(() => {
      if (!isEdit) {
        setName(''); setUsername(''); setEmail(''); setPhone('');
        setPassword(''); setRoleId(''); setContractorId(''); setStatus('active');
      }
    }, [isEdit])
  );

  // Clear contractor when role changes to an admin type
  useEffect(() => {
    if (roleId && !isContractorRole) {
      setContractorId('');
    }
  }, [roleId]);

  useEffect(() => {
    api.get('/mobile/admin/roles').then((res) => {
      setRolesData((res.data ?? []).map((r: { id: number; name: string; type: string }) => ({
        id: r.id,
        name: r.name,
        type: r.type,
      })));
    }).catch(() => {});

    api.get('/mobile/contractors').then((res) => {
      setContractors((res.data ?? []).map((c: { id: number; name: string }) => ({
        label: c.name,
        value: String(c.id),
      })));
    }).catch(() => {});

    if (!id) { setLoading(false); return; }
    api.get(`/mobile/admin/users/${id}`).then((res) => {
      const u = res.data;
      setName(u.name ?? '');
      setUsername(u.username ?? '');
      setEmail(u.email ?? '');
      setPhone(u.phone ?? '');
      setRoleId(u.role_id ? String(u.role_id) : '');
      setContractorId(u.contractor_id ? String(u.contractor_id) : '');
      setStatus(u.status ?? 'active');
    }).catch(() => {}).finally(() => setLoading(false));
  }, [id]);

  async function handleSave() {
    if (!name.trim()) { Alert.alert('Required', 'Full name is required.'); return; }
    if (!username.trim()) { Alert.alert('Required', 'Username is required.'); return; }
    if (!isEdit && !password.trim()) { Alert.alert('Required', 'Password is required.'); return; }
    if (!roleId) { Alert.alert('Required', 'Please select a role.'); return; }
    if (isContractorRole && !contractorId) { Alert.alert('Required', 'Please select a company for this role.'); return; }

    setSaving(true);
    try {
      const body: Record<string, string | null> = {
        name: name.trim(),
        username: username.trim(),
        email: email.trim() || null,
        phone: phone.trim() || null,
        role_id: roleId,
        contractor_id: isContractorRole ? contractorId : null,
      };

      if (!isEdit) {
        body.password = password;
      }

      if (isEdit) {
        body.status = status;
        await api.put(`/mobile/admin/users/${id}`, body);
        Alert.alert('Saved', 'User updated.', [{ text: 'OK', onPress: () => goBack() }]);
      } else {
        await api.post('/mobile/admin/users', body);
        Alert.alert('Created', 'User added.', [{ text: 'OK', onPress: () => router.navigate('/(tabs)/admin/users') }]);
      }
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.message ?? 'Failed to save.');
    } finally {
      setSaving(false);
    }
  }

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader
        title={isEdit ? 'Edit User' : 'New User'}
        subtitle={isEdit ? 'Update user account' : 'Fill in the details below'}
        right={<HeaderSaveButton onPress={handleSave} saving={saving} label={isEdit ? 'Update' : 'Save'} />}
      />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">
          <Field label="Full Name" required>
            <Input value={name} onChangeText={setName} placeholder="e.g. Rajesh Kumar" />
          </Field>

          <Field label="Username" required>
            <Input value={username} onChangeText={setUsername} placeholder="e.g. rajesh.kumar" autoCapitalize="none" />
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

          {!isEdit && (
            <Field label="Password" required>
              <Input value={password} onChangeText={setPassword} placeholder="Min. 6 characters" secureTextEntry />
            </Field>
          )}

          <SelectPicker
            label="Role"
            value={roleId}
            options={roleOptions}
            onChange={setRoleId}
            placeholder="Select role…"
            searchable
            required
          />

          {isContractorRole && (
            <SelectPicker
              label="Company"
              value={contractorId}
              options={contractors}
              onChange={setContractorId}
              placeholder="Select company…"
              searchable
              required
            />
          )}

          {isEdit && (
            <SelectPicker
              label="Status"
              value={status}
              options={STATUS_OPTIONS}
              onChange={setStatus}
            />
          )}

          <TouchableOpacity style={s.submitBtn} onPress={handleSave} disabled={saving} activeOpacity={0.85}>
            {saving
              ? <ActivityIndicator color={Colors.primary} />
              : <Text style={s.submitBtnText}>{isEdit ? 'Update User' : 'Add User'}</Text>
            }
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
  row2: { flexDirection: 'row' },
  submitBtn: {
    backgroundColor: Colors.accent, borderRadius: 12, height: 50,
    alignItems: 'center', justifyContent: 'center', marginTop: 8,
  },
  submitBtnText: { fontSize: 16, fontWeight: '800', color: Colors.primary },
});
