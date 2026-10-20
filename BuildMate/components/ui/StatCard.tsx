import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { Colors } from '@/constants/Colors';

type Props = {
  label: string;
  value: string | number;
  note?: string;
  accent?: 'primary' | 'accent' | 'success' | 'warning' | 'danger' | 'info';
  onPress?: () => void;
};

const ACCENT_MAP = {
  primary: { bg: Colors.primary + '12', icon: Colors.primary },
  accent: { bg: Colors.accent + '20', icon: Colors.accent },
  success: { bg: Colors.successBg, icon: Colors.success },
  warning: { bg: Colors.warningBg, icon: Colors.warning },
  danger: { bg: Colors.dangerBg, icon: Colors.danger },
  info: { bg: Colors.infoBg, icon: Colors.info },
};

export function StatCard({ label, value, note, accent = 'primary', onPress }: Props) {
  const colors = ACCENT_MAP[accent];
  return (
    <TouchableOpacity style={[styles.card, { borderLeftColor: colors.icon }]} onPress={onPress} activeOpacity={onPress ? 0.7 : 1}>
      <Text style={styles.value}>{value}</Text>
      <Text style={styles.label}>{label}</Text>
      {note && <Text style={styles.note}>{note}</Text>}
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: Colors.card,
    borderRadius: 12,
    padding: 16,
    flex: 1,
    borderLeftWidth: 3,
    shadowColor: Colors.shadow,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 1,
    shadowRadius: 4,
    elevation: 2,
  },
  value: {
    fontSize: 22,
    fontWeight: '800',
    color: Colors.text,
    marginBottom: 2,
  },
  label: {
    fontSize: 12,
    color: Colors.textSecondary,
    fontWeight: '600',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  note: {
    fontSize: 11,
    color: Colors.textMuted,
    marginTop: 4,
  },
});
