import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Colors } from '@/constants/Colors';

type Variant = 'planning' | 'active' | 'on_hold' | 'completed' | 'cancelled' | 'open' | 'in_progress' | 'done' | 'high' | 'medium' | 'low' | 'default';

const VARIANT_MAP: Record<string, { bg: string; text: string }> = {
  planning:    { bg: Colors.infoBg,    text: Colors.info },
  active:      { bg: Colors.successBg, text: Colors.success },
  on_hold:     { bg: Colors.warningBg, text: Colors.warning },
  completed:   { bg: '#E0F2FE',        text: '#0369A1' },
  cancelled:   { bg: '#F1F5F9',        text: Colors.textMuted },
  open:        { bg: Colors.warningBg, text: Colors.warning },
  in_progress: { bg: Colors.infoBg,    text: Colors.info },
  done:        { bg: Colors.successBg, text: Colors.success },
  high:        { bg: Colors.dangerBg,  text: Colors.danger },
  medium:      { bg: Colors.warningBg, text: Colors.warning },
  low:         { bg: '#F1F5F9',        text: Colors.textSecondary },
  default:     { bg: '#F1F5F9',        text: Colors.textSecondary },
};

type Props = {
  status: string;
  label?: string;
};

export function Badge({ status, label }: Props) {
  const key = status?.toLowerCase().replace(/ /g, '_');
  const colors = VARIANT_MAP[key] ?? VARIANT_MAP.default;
  const text = label ?? status?.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

  return (
    <View style={[styles.badge, { backgroundColor: colors.bg }]}>
      <Text style={[styles.text, { color: colors.text }]}>{text}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
    alignSelf: 'flex-start',
  },
  text: {
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0.3,
  },
});
