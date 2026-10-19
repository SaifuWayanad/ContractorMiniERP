import React, { useCallback, useEffect, useState } from 'react';
import {
  View, Text, ScrollView, StyleSheet, TextInput,
  TouchableOpacity, ActivityIndicator, Alert, KeyboardAvoidingView, Platform, Switch,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams, useFocusEffect } from 'expo-router';
import { ScreenHeader, HeaderSaveButton } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

function Field({ label, required, children }: { label: string; required?: boolean; children: React.ReactNode }) {
  return (
    <View style={s.field}>
      <Text style={s.label}>{label}{required && <Text style={{ color: Colors.danger }}> *</Text>}</Text>
      {children}
    </View>
  );
}

function Input(props: React.ComponentProps<typeof TextInput>) {
  return <TextInput style={s.input} placeholderTextColor={Colors.textMuted} {...props} />;
}

function toUsername(name: string): string {
  return name.toLowerCase().replace(/\s+/g, '.').replace(/[^a-z0-9.]/g, '');
}

export default function ContractorFormScreen() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  const isEdit = !!id;

  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);

  // Contractor fields
  const [companyName, setCompanyName] = useState('');
  const [contactPerson, setContactPerson] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [city, setCity] = useState('');
  const [stateVal, setStateVal] = useState('');

  // Login creation (create only)
  const [createLogin, setCreateLogin] = useState(false);
  const [userName, setUserName] = useState('');
  const [username, setUsername] = useState('');
  const [userPassword, setUserPassword] = useState('');
  const [usernameEdited, setUsernameEdited] = useState(false);

  useFocusEffect(
    useCallback(() => {
      if (!isEdit) {
        setCompanyName(''); setContactPerson(''); setEmail('');
        setPhone(''); setCity(''); setStateVal('');
        setCreateLogin(false); setUserName(''); setUsername('');
        setUserPassword(''); setUsernameEdited(false);
      }
    }, [isEdit])
  );

  // Auto-generate username from owner name unless manually edited
  useEffect(() => {
    if (!usernameEdited && userName) {
      setUsername(toUsername(userName));
    }
  }, [userName, usernameEdited]);

  useEffect(() => {
    if (!id) { setLoading(false); return; }
    api.get(`/mobile/admin/contractors/${id}`).then((res) => {
      const c = res.data;
      setCompanyName(c.company_name ?? '');
      setContactPerson(c.contact_person ?? '');
      setEmail(c.email ?? '');
      setPhone(c.phone ?? '');
      setCity(c.city ?? '');
      setStateVal(c.state ?? '');
    }).catch(() => {}).finally(() => setLoading(false));
  }, [id]);

  async function handleSave() {
    if (!companyName.trim()) { Alert.alert('Required', 'Company name is required.'); return; }
    if (createLogin) {
      if (!userName.trim()) { Alert.alert('Required', 'Owner name is required to create a login.'); return; }
      if (!userPassword.trim() || userPassword.length < 6) { Alert.alert('Required', 'Password must be at least 6 characters.'); return; }
    }

    setSaving(true);
    try {
      const body: Record<string, string | boolean | null> = {
        company_name: companyName.trim(),
        contact_person: contactPerson.trim() || null,
        email: email.trim() || null,
        phone: phone.trim() || null,
        city: city.trim() || null,
        state: stateVal.trim() || null,
      };

      if (!isEdit && createLogin) {
        body.create_user   = true;
        body.user_name     = userName.trim();
        body.username      = username.trim() || null;
        body.user_password = userPassword;
      }

      if (isEdit) {
        await api.put(`/mobile/admin/contractors/${id}`, body);
        Alert.alert('Saved', 'Contractor updated.', [{ text: 'OK', onPress: () => router.back() }]);
      } else {
        await api.post('/mobile/admin/contractors', body);
        Alert.alert('Created', 'Contractor added.', [{ text: 'OK', onPress: () => router.navigate('/(tabs)/admin/contractors') }]);
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
        title={isEdit ? 'Edit Contractor' : 'New Contractor'}
        subtitle={isEdit ? 'Update contractor info' : 'Fill in the details below'}
        right={<HeaderSaveButton onPress={handleSave} saving={saving} label={isEdit ? 'Update' : 'Save'} />}
      />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">

          {/* Company info */}
          <Field label="Company Name" required>
            <Input value={companyName} onChangeText={setCompanyName} placeholder="e.g. Sharma Constructions" />
          </Field>

          <Field label="Contact Person">
            <Input value={contactPerson} onChangeText={setContactPerson} placeholder="e.g. Rajesh Sharma" />
          </Field>

          <View style={s.row2}>
            <View style={{ flex: 1, marginRight: 8 }}>
              <Field label="Phone">
                <Input value={phone} onChangeText={setPhone} placeholder="+91 9876543210" keyboardType="phone-pad" />
              </Field>
            </View>
            <View style={{ flex: 1 }}>
              <Field label="Email">
                <Input value={email} onChangeText={setEmail} placeholder="info@example.com" keyboardType="email-address" autoCapitalize="none" />
              </Field>
            </View>
          </View>

          <View style={s.row2}>
            <View style={{ flex: 1, marginRight: 8 }}>
              <Field label="City">
                <Input value={city} onChangeText={setCity} placeholder="City" />
              </Field>
            </View>
            <View style={{ flex: 1 }}>
              <Field label="State">
                <Input value={stateVal} onChangeText={setStateVal} placeholder="State" />
              </Field>
            </View>
          </View>

          {/* Login creation toggle — only on create */}
          {!isEdit && (
            <>
              <TouchableOpacity
                style={[s.toggleRow, createLogin && s.toggleRowActive]}
                onPress={() => setCreateLogin((v) => !v)}
                activeOpacity={0.8}
              >
                <View style={s.toggleLeft}>
                  <View style={[s.toggleIcon, createLogin && s.toggleIconActive]}>
                    <Ionicons name="person-add" size={18} color={createLogin ? Colors.white : Colors.textMuted} />
                  </View>
                  <View>
                    <Text style={[s.toggleTitle, createLogin && s.toggleTitleActive]}>Create Login Account</Text>
                    <Text style={s.toggleSub}>Add owner login for this contractor</Text>
                  </View>
                </View>
                <Switch
                  value={createLogin}
                  onValueChange={setCreateLogin}
                  trackColor={{ true: Colors.accent, false: Colors.border }}
                  thumbColor={Colors.white}
                />
              </TouchableOpacity>

              {createLogin && (
                <View style={s.loginSection}>
                  <Field label="Owner Name" required>
                    <Input
                      value={userName}
                      onChangeText={setUserName}
                      placeholder="e.g. Rajesh Sharma"
                    />
                  </Field>

                  <Field label="Username">
                    <Input
                      value={username}
                      onChangeText={(v) => { setUsername(v); setUsernameEdited(true); }}
                      placeholder="Auto-generated from name"
                      autoCapitalize="none"
                      autoCorrect={false}
                    />
                    <Text style={s.hint}>Leave blank to auto-generate from owner name</Text>
                  </Field>

                  <Field label="Password" required>
                    <Input
                      value={userPassword}
                      onChangeText={setUserPassword}
                      placeholder="Min. 6 characters"
                      secureTextEntry
                    />
                  </Field>
                </View>
              )}
            </>
          )}

          <TouchableOpacity style={s.submitBtn} onPress={handleSave} disabled={saving} activeOpacity={0.85}>
            {saving
              ? <ActivityIndicator color={Colors.primary} />
              : <Text style={s.submitBtnText}>{isEdit ? 'Update Contractor' : 'Add Contractor'}</Text>
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
  hint: { fontSize: 11, color: Colors.textMuted, marginTop: 4 },
  toggleRow: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    backgroundColor: Colors.white, borderRadius: 12, padding: 14,
    borderWidth: 1.5, borderColor: Colors.border, marginBottom: 14,
  },
  toggleRowActive: { borderColor: Colors.accent, backgroundColor: `${Colors.accent}08` },
  toggleLeft: { flexDirection: 'row', alignItems: 'center', gap: 12, flex: 1 },
  toggleIcon: {
    width: 36, height: 36, borderRadius: 10,
    backgroundColor: Colors.background, alignItems: 'center', justifyContent: 'center',
  },
  toggleIconActive: { backgroundColor: Colors.accent },
  toggleTitle: { fontSize: 14, fontWeight: '700', color: Colors.text },
  toggleTitleActive: { color: Colors.accent },
  toggleSub: { fontSize: 12, color: Colors.textMuted, marginTop: 1 },
  loginSection: {
    backgroundColor: Colors.white, borderRadius: 12, padding: 14,
    borderWidth: 1.5, borderColor: Colors.accent, marginBottom: 14,
  },
  submitBtn: {
    backgroundColor: Colors.accent, borderRadius: 12, height: 50,
    alignItems: 'center', justifyContent: 'center', marginTop: 8,
  },
  submitBtnText: { fontSize: 16, fontWeight: '800', color: Colors.primary },
});
