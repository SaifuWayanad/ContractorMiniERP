import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Colors } from '@/constants/Colors';

type Props = {
  title: string;
  onBack: () => void;
  right?: React.ReactNode;
};

/** Header for modal screens and selection sheets: back arrow on the left closes the modal. */
export function ModalHeader({ title, onBack, right }: Props) {
  return (
    <View style={s.header}>
      <TouchableOpacity onPress={onBack} style={s.backBtn} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }} accessibilityLabel="Back">
        <Ionicons name="chevron-back" size={24} color={Colors.text} />
      </TouchableOpacity>
      <Text style={s.title} numberOfLines={1}>{title}</Text>
      <View style={s.right}>{right}</View>
    </View>
  );
}

const s = StyleSheet.create({
  header: {
    flexDirection: 'row', alignItems: 'center',
    paddingHorizontal: 12, paddingVertical: 12,
    borderBottomWidth: 1, borderBottomColor: Colors.border,
    backgroundColor: Colors.white,
  },
  backBtn: { width: 36, height: 36, alignItems: 'center', justifyContent: 'center' },
  title: { flex: 1, fontSize: 17, fontWeight: '700', color: Colors.text, textAlign: 'center' },
  right: { width: 36, alignItems: 'flex-end' },
});
