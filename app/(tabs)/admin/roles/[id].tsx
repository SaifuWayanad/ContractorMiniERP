import React, { useCallback, useEffect, useState } from 'react';
import { View, Text, ScrollView, StyleSheet, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useLocalSearchParams } from 'expo-router';
import { Card } from '@/components/ui/Card';
import { ScreenHeader } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type Permission = { id: number; name: string; description: string | null };

type RoleDetail = {
  id: number;
  name: string;
  type: string;
  is_system: boolean;
  users_count: number;
  permissions: Record<string, Permission[]>;
};

const TYPE_LABEL: Record<string, string> = {
  admin: 'Admin portal',
  contractor: 'Contractor portal',
};

export default function RoleDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [role, setRole] = useState<RoleDetail | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    try {
      const res = await api.get(`/mobile/admin/roles/${id}`);
      setRole(res.data);
    } catch { /* ignore */ } finally { setLoading(false); }
  }, [id]);

  useEffect(() => { load(); }, [load]);

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;
  if (!role) return <View style={s.centered}><Text style={{ color: Colors.textMuted }}>Role not found.</Text></View>;

  const groups = Object.entries(role.permissions);

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader title={role.name} subtitle="Role & permissions" />
      <ScrollView contentContainerStyle={s.content} showsVerticalScrollIndicator={false}>
        <Card title="Role Info">
          <View style={s.row}><Text style={s.label}>Type</Text><Text style={s.value}>{TYPE_LABEL[role.type] ?? role.type}</Text></View>
          <View style={s.row}>
            <Text style={s.label}>System Role</Text>
            <View style={[s.badge, { backgroundColor: role.is_system ? `${Colors.warning}20` : `${Colors.textMuted}15` }]}>
              <Text style={[s.badgeText, { color: role.is_system ? Colors.warning : Colors.textMuted }]}>
                {role.is_system ? 'Yes' : 'No'}
              </Text>
            </View>
          </View>
          <View style={[s.row, { borderBottomWidth: 0 }]}>
            <Text style={s.label}>Users</Text>
            <Text style={s.value}>{role.users_count}</Text>
          </View>
        </Card>

        {groups.length > 0 && (
          <Card title={`Permissions (${Object.values(role.permissions).flat().length})`}>
            {groups.map(([group, perms], gi) => (
              <View key={group}>
                {gi > 0 && <View style={s.groupDivider} />}
                <Text style={s.groupTitle}>{group || 'General'}</Text>
                {perms.map((p, i) => (
                  <View key={p.id} style={[s.permRow, i < perms.length - 1 && s.permBorder]}>
                    <View style={s.permDot} />
                    <View style={{ flex: 1 }}>
                      <Text style={s.permName}>{p.name}</Text>
                      {p.description && <Text style={s.permDesc}>{p.description}</Text>}
                    </View>
                  </View>
                ))}
              </View>
            ))}
          </Card>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  content: { padding: 16, paddingBottom: 40 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  row: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: Colors.border,
  },
  label: { fontSize: 13, color: Colors.textSecondary },
  value: { fontSize: 13, fontWeight: '600', color: Colors.text },
  badge: { borderRadius: 4, paddingHorizontal: 8, paddingVertical: 3 },
  badgeText: { fontSize: 12, fontWeight: '600' },
  groupDivider: { height: 1, backgroundColor: Colors.border, marginVertical: 10 },
  groupTitle: {
    fontSize: 11, fontWeight: '700', color: Colors.textMuted,
    textTransform: 'uppercase', letterSpacing: 0.7, marginBottom: 6,
  },
  permRow: { flexDirection: 'row', alignItems: 'flex-start', paddingVertical: 8 },
  permBorder: { borderBottomWidth: 1, borderBottomColor: Colors.border },
  permDot: {
    width: 6, height: 6, borderRadius: 3,
    backgroundColor: Colors.accent, marginTop: 5, marginRight: 10,
  },
  permName: { fontSize: 13, fontWeight: '600', color: Colors.text },
  permDesc: { fontSize: 12, color: Colors.textMuted, marginTop: 2 },
});
