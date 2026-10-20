import React, { useCallback, useEffect, useState } from 'react';
import {
  View, Text, ScrollView, StyleSheet, TextInput,
  TouchableOpacity, ActivityIndicator, Alert, KeyboardAvoidingView, Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, useLocalSearchParams, useFocusEffect } from 'expo-router';
import { ScreenHeader, HeaderSaveButton } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import { useAuth } from '@/hooks/useAuth';
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
  return (
    <TextInput
      style={s.input}
      placeholderTextColor={Colors.textMuted}
      {...props}
    />
  );
}

export default function DealerFormScreen() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  const { user } = useAuth();
  const isEdit = !!id;

  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);
  const [name, setName] = useState('');
  const [city, setCity] = useState('');
  const [phone, setPhone] = useState('');

  useFocusEffect(
    useCallback(() => {
      if (!isEdit) {
        setName(''); setCity(''); setPhone('');
      }
    }, [isEdit])
  );

  const loadDealer = useCallback(async () => {
    if (!id) return;
    try {
      const res = await api.get(`/mobile/dealers/${id}`);
      setName(res.data.name ?? '');
      setCity(res.data.city ?? '');
      setPhone(res.data.phone ?? '');
    } catch { /* ignore */ } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    loadDealer();
    if (!isEdit) setLoading(false);
  }, [loadDealer, isEdit]);

  async function handleSave() {
    if (!name.trim()) { Alert.alert('Required', 'Name is required.'); return; }

    setSaving(true);
    try {
      const body: Record<string, string> = { name: name.trim() };
      if (city.trim()) body.city = city.trim();
      if (phone.trim()) body.phone = phone.trim();

      if (isEdit) {
        await api.put(`/mobile/dealers/${id}`, body);
        Alert.alert('Saved', 'Contact updated.', [{ text: 'OK', onPress: () => router.back() }]);
      } else {
        await api.post('/mobile/dealers', body);
        Alert.alert('Created', 'Contact added.', [{ text: 'OK', onPress: () => router.navigate('/(tabs)/dealers') }]);
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
        title={isEdit ? 'Edit Contact' : 'New Contact'}
        subtitle={isEdit ? 'Update dealer / contact info' : 'Fill in the details below'}
        right={<HeaderSaveButton onPress={handleSave} saving={saving} label={isEdit ? 'Update' : 'Save'} />}
      />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">

          <Field label="Name" required>
            <Input value={name} onChangeText={setName} placeholder="e.g. Ravi Tiles & Marble" />
          </Field>

          <Field label="City">
            <Input value={city} onChangeText={setCity} placeholder="e.g. Chennai" />
          </Field>

          <Field label="Phone">
            <Input value={phone} onChangeText={setPhone} placeholder="+91 9876543210" keyboardType="phone-pad" />
          </Field>

          <TouchableOpacity style={s.submitBtn} onPress={handleSave} disabled={saving} activeOpacity={0.85}>
            {saving
              ? <ActivityIndicator color={Colors.primary} />
              : <Text style={s.submitBtnText}>{isEdit ? 'Update Contact' : 'Add Contact'}</Text>}
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
  submitBtn: {
    backgroundColor: Colors.accent, borderRadius: 12, height: 50,
    alignItems: 'center', justifyContent: 'center', marginTop: 8,
  },
  submitBtnText: { fontSize: 16, fontWeight: '800', color: Colors.primary },
});
