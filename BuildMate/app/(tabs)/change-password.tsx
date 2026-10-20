import React, { useState } from 'react';
import {
  View, Text, ScrollView, StyleSheet, TextInput,
  TouchableOpacity, ActivityIndicator, Alert, KeyboardAvoidingView, Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ScreenHeader } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';
import { goBack } from '@/services/navigation';

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <View style={s.field}>
      <Text style={s.label}>{label}</Text>
      {children}
    </View>
  );
}

function PasswordInput(props: React.ComponentProps<typeof TextInput>) {
  const [visible, setVisible] = useState(false);
  return (
    <View style={s.passwordWrap}>
      <TextInput
        style={s.passwordInput}
        placeholderTextColor={Colors.textMuted}
        secureTextEntry={!visible}
        autoCapitalize="none"
        {...props}
      />
      <TouchableOpacity onPress={() => setVisible((v) => !v)} style={s.eyeBtn} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
        <Text style={s.eyeIcon}>{visible ? '🙈' : '👁'}</Text>
      </TouchableOpacity>
    </View>
  );
}

export default function ChangePasswordScreen() {
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [saving, setSaving] = useState(false);

  async function handleSave() {
    if (!currentPassword || !newPassword || !confirmPassword) {
      Alert.alert('Required', 'All fields are required.');
      return;
    }
    if (newPassword !== confirmPassword) {
      Alert.alert('Mismatch', 'New password and confirm password do not match.');
      return;
    }
setSaving(true);
    try {
      await api.put('/mobile/account/password', {
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: confirmPassword,
      });
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      Alert.alert('Done', 'Your password has been changed successfully.', [{ text: 'OK', onPress: () => goBack() }]);
    } catch (err: any) {
      const msg =
        err?.response?.data?.errors?.current_password?.[0] ??
        err?.response?.data?.errors?.password?.[0] ??
        err?.response?.data?.message ??
        'Failed to change password.';
      Alert.alert('Error', msg);
    } finally {
      setSaving(false);
    }
  }

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader title="Change Password" subtitle="Enter your current and new password" />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={s.content} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>

          <View style={s.infoCard}>
            <Text style={s.infoText}>Your new password must be different from your current password.</Text>
          </View>

          <Field label="Current Password">
            <PasswordInput
              value={currentPassword}
              onChangeText={setCurrentPassword}
              placeholder="Enter your current password"
            />
          </Field>

          <Field label="New Password">
            <PasswordInput
              value={newPassword}
              onChangeText={setNewPassword}
              placeholder="Enter new password"
            />
          </Field>

          <Field label="Confirm New Password">
            <PasswordInput
              value={confirmPassword}
              onChangeText={setConfirmPassword}
              placeholder="Repeat new password"
            />
          </Field>

          <TouchableOpacity style={s.submitBtn} onPress={handleSave} disabled={saving} activeOpacity={0.85}>
            {saving
              ? <ActivityIndicator color={Colors.primary} />
              : <Text style={s.submitBtnText}>Change Password</Text>}
          </TouchableOpacity>

        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  content: { padding: 16, paddingBottom: 40 },
  infoCard: {
    backgroundColor: Colors.infoBg, borderRadius: 10, padding: 14,
    marginBottom: 20, borderLeftWidth: 3, borderLeftColor: Colors.info,
  },
  infoText: { fontSize: 13, color: Colors.info, lineHeight: 20 },
  field: { marginBottom: 14 },
  label: { fontSize: 13, fontWeight: '600', color: Colors.text, marginBottom: 6 },
  passwordWrap: {
    flexDirection: 'row', alignItems: 'center',
    borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10,
    backgroundColor: Colors.white, paddingHorizontal: 12, height: 48,
  },
  passwordInput: { flex: 1, fontSize: 15, color: Colors.text },
  eyeBtn: { padding: 4 },
  eyeIcon: { fontSize: 16 },
  submitBtn: {
    backgroundColor: Colors.accent, borderRadius: 12, height: 50,
    alignItems: 'center', justifyContent: 'center', marginTop: 8,
  },
  submitBtnText: { fontSize: 15, fontWeight: '800', color: Colors.primary },
});
