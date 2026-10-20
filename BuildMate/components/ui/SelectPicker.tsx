import React, { useState } from 'react';
import {
  Modal, View, Text, TouchableOpacity, FlatList,
  TextInput, StyleSheet,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { Colors } from '@/constants/Colors';
import { ModalHeader } from '@/components/ui/ModalHeader';

export type PickerOption = { label: string; value: string };

type Props = {
  label: string;
  value: string | null;
  options: PickerOption[];
  onChange: (value: string) => void;
  placeholder?: string;
  searchable?: boolean;
  required?: boolean;
  onAddNew?: () => void;
  addNewLabel?: string;
};

export function SelectPicker({
  label, value, options, onChange, placeholder = 'Select…', searchable = false, required, onAddNew, addNewLabel = 'Create new',
}: Props) {
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState('');

  const selected = options.find((o) => o.value === value);
  const filtered = searchable
    ? options.filter((o) => o.label.toLowerCase().includes(search.toLowerCase()))
    : options;

  return (
    <>
      <View style={s.fieldWrap}>
        <Text style={s.label}>{label}{required && <Text style={{ color: Colors.danger }}> *</Text>}</Text>
        <TouchableOpacity style={s.selector} onPress={() => { setSearch(''); setOpen(true); }} activeOpacity={0.7}>
          <Text style={[s.selectorText, !selected && { color: Colors.textMuted }]}>
            {selected?.label ?? placeholder}
          </Text>
          <Ionicons name="chevron-down" size={16} color={Colors.textMuted} />
        </TouchableOpacity>
      </View>

      <Modal visible={open} animationType="slide" presentationStyle="pageSheet">
        <SafeAreaView style={s.modal}>
          <ModalHeader title={label} onBack={() => setOpen(false)} />
          {searchable && (
            <View style={s.searchBar}>
              <Ionicons name="search" size={16} color={Colors.textMuted} style={{ marginRight: 8 }} />
              <TextInput
                style={s.searchInput}
                value={search}
                onChangeText={setSearch}
                placeholder="Search…"
                placeholderTextColor={Colors.textMuted}
                autoFocus
              />
            </View>
          )}
          <FlatList
            data={filtered}
            keyExtractor={(item) => item.value}
            renderItem={({ item }) => (
              <TouchableOpacity
                style={[s.option, item.value === value && s.optionActive]}
                onPress={() => { onChange(item.value); setOpen(false); }}
              >
                <Text style={[s.optionText, item.value === value && s.optionTextActive]}>{item.label}</Text>
                {item.value === value && <Ionicons name="checkmark" size={18} color={Colors.accent} />}
              </TouchableOpacity>
            )}
            ItemSeparatorComponent={() => <View style={s.sep} />}
            ListFooterComponent={onAddNew ? (
              <TouchableOpacity
                style={s.addNew}
                onPress={() => {
                  setOpen(false);
                  // Let the picker finish closing before another modal opens (iOS won't stack them otherwise).
                  setTimeout(onAddNew, 350);
                }}
                activeOpacity={0.7}
              >
                <Ionicons name="add-circle-outline" size={20} color={Colors.accent} />
                <Text style={s.addNewText}>{addNewLabel}</Text>
              </TouchableOpacity>
            ) : null}
          />
        </SafeAreaView>
      </Modal>
    </>
  );
}

const s = StyleSheet.create({
  fieldWrap: { marginBottom: 14 },
  label: { fontSize: 13, fontWeight: '600', color: Colors.text, marginBottom: 6 },
  selector: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10,
    backgroundColor: Colors.white, paddingHorizontal: 12, height: 48,
  },
  selectorText: { fontSize: 15, color: Colors.text, flex: 1 },
  modal: { flex: 1, backgroundColor: Colors.background },
  modalHeader: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    padding: 16, borderBottomWidth: 1, borderBottomColor: Colors.border,
    backgroundColor: Colors.white,
  },
  modalTitle: { fontSize: 17, fontWeight: '700', color: Colors.text },
  searchBar: {
    flexDirection: 'row', alignItems: 'center',
    margin: 12, paddingHorizontal: 12, backgroundColor: Colors.white,
    borderRadius: 10, height: 42, borderWidth: 1, borderColor: Colors.border,
  },
  searchInput: { flex: 1, fontSize: 15, color: Colors.text },
  option: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingVertical: 14, paddingHorizontal: 16, backgroundColor: Colors.white,
  },
  optionActive: { backgroundColor: Colors.accent + '10' },
  optionText: { fontSize: 15, color: Colors.text },
  optionTextActive: { color: Colors.accent, fontWeight: '700' },
  sep: { height: 1, backgroundColor: Colors.border, marginLeft: 16 },
  addNew: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    paddingVertical: 14, paddingHorizontal: 16, marginTop: 8,
    backgroundColor: Colors.white, borderTopWidth: 1, borderTopColor: Colors.border,
  },
  addNewText: { fontSize: 15, fontWeight: '700', color: Colors.accent },
});
